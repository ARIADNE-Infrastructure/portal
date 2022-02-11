<template>
  <div
    v-show="map"
    class="relative mb-lg rounded-base border-base border-gray"
  >
    <!-- map frame controls -->
    <div class="bg-lightGray items-center p-md flex justify-between">
      <span class="text-md">{{ title }}</span>

      <div
        v-if="hasHits"
        class="flex items-center"
      >
        <help-tooltip
          title="Search for resources in this area"
          top="-.6rem"
          right="2rem"
        >
          <i
            class="fas fa-search mr-xs text-blue transition-color duration-300 hover:text-green cursor-pointer"
            @click.prevent="syncGlobalSearchWithMiniMap('/search')"
          />
        </help-tooltip>

        <help-tooltip
          title="Show in fullscreen"
          top="-.6rem"
          right="4rem"
        >
          <i
            class="fas fa-expand transition-color duration-300 hover:text-green px-sm cursor-pointer"
            @click.prevent="syncGlobalSearchWithMiniMap('/browse/where?mapq=true')"
          />
        </help-tooltip>
      </div>
    </div>

    <div
      class="relative"
      style="height: 250px;"
    >
      <div
        v-if="!hasHits"
        class="absolute top-0 left-0 h-full w-full bg-gray-70 z-1001 text-darkGray text-xl flex items-center justify-center"
      >
        No locations found
      </div>

      <!-- map -->
      <div
        id="map"
        style="height: 250px;"
      />
    </div>

    <!-- map info -->
    <transition
      v-on:before-enter="showMarkerInfoLeave" v-on:enter="showMarkerInfoEnter"
      v-on:before-leave="showMarkerInfoEnter" v-on:leave="showMarkerInfoLeave"
    >
      <div
        v-if="showMarkerInfo"
        class="bg-lightGray ease-out duration-200 overflow-hidden"
      >
        <div class="max-w-screen-xl mx-auto">
          <div class="block md:flex md:justify-between items-center text-center text-midGray text-md">
            <ul class="py-md px-base flex">
              <li class="flex items-center h-2x">
                <img
                  :src="mapUtils.markerType.point.marker"
                  width="15"
                  class="inline mr-sm"
                  alt="similar marker"
                >
                Geo point
              </li>
              <li class="ml-lg flex items-center h-2x">
                <img
                  :src="mapUtils.markerType.point.shape"
                  width="15"
                  class="inline mr-sm"
                  alt="current marker"
                >
                Geo shape
              </li>
              <li class="ml-lg flex items-center h-2x">
                <img
                  :src="mapUtils.markerType.combo.marker"
                  width="20"
                  class="inline mr-sm"
                  alt="current marker"
                >
                Approx. location
              </li>
            </ul>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Prop, Watch } from "vue-property-decorator";
import { searchModule, generalModule } from "@/store/modules";

import * as L from "leaflet";
import Geohash from "latlon-geohash";
import utils from '@/utils/utils';
import mapUtils from "@/utils/map";

// Leaflet stuff
import "leaflet.heat";
import "leaflet.markercluster";
import "leaflet/dist/leaflet.css";

import "leaflet.markercluster/dist/MarkerCluster.css";
import "leaflet.markercluster/dist/MarkerCluster.Default.css";

// WKT reader/helper for map
import "leaflet-draw";
import "leaflet-draw/dist/leaflet.draw.css";

// WKT reader/helper for map
import 'wicket/wicket-leaflet';
import Wkt from 'wicket';

import HelpTooltip from "@/component/Help/Tooltip.vue";

@Component({
  components: {
    HelpTooltip,
  },
})
export default class ResultMiniMap extends Vue {
  @Prop() title?: string;
  @Prop() height!: string;
  @Prop() noZoom?: boolean;

  // Render cluster markers when this limit is reached
  markerThreshold: number = 500;

  map: any = null;
  clusterMarkers: L.MarkerClusterGroup | null = null;
  heatMap: L.HeatLayer | null = null;
  drawLayer: any = null;
  showMarkerInfo?: boolean = false;
  mapUtils = mapUtils;
  mapBodyHasBeenSetup: boolean = false;
  mapBodyHasZoomendDragend: boolean = false;

  // updates as soon as either map body or result state has been changed
  get miniMapBodyOrSearchResultUpdates(): any {
    return {
      'setup': this.mapBodyHasBeenSetup,
      ...this.miniMapSearchResult,
    }
  }

  get isLoading(): boolean {
    return generalModule.getIsLoading;
  }

  get hasHits(): boolean {

    if(this.heatMap || this.clusterMarkers) {
      return true;
    }
    // skip/return true if not everything has been properly loaded yet
    if (!this.mapBodyHasBeenSetup || !utils.objectIsNotEmpty(this.globalResult)) {
      return true;
    }

    // get if locations exists in any resource
    return this.globalResult.hits.some((hit: any) => {
      return hit.data?.spatial.some((spatial: any) => {
        return spatial?.geopoint || spatial?.polygon || spatial?.boundingbox ? true : false;
      });
    });

  }

  get globalParams(): any {
    return searchModule.getParams;
  }

  get globalResult(): any {
    return searchModule.getResult;
  }

  get assets(): string {
    return generalModule.getAssetsDir;
  }

  get miniMapSearchParams(): any {
    return searchModule.getMiniMapSearchParams;
  }

  get miniMapSearchResult(): any {
    return searchModule.getMiniMapSearchResult;
  }

  mounted() {
    // prepares this.map with an L.map()
    this.setupMiniMapBody();
  }

  destroyed() {
    // reset minimap state when switching pages
    searchModule.setMiniMapSearch( { ...this.globalParams });
  }

  // show marker info
  showMarkerInfoEnter(el: any): void {
    el.style.height = `${ el.scrollHeight}px`;
  }

  // hide marker info
  showMarkerInfoLeave(el: any): void {
    el.style.height = '0px';
  }

  // sets up map body
  setupMiniMapBody(): void {
    this.map = L.map("map", {
      zoomControl: false,
      scrollWheelZoom: !this.noZoom,
      wheelDebounceTime: 300,
      worldCopyJump: true,
      maxBounds: L.latLngBounds([-90,-180],[90,180]),

      // This needs to be here for Safari users.
      // If not the the marker popups don't work! TODO: test for tabs and smartphones!
      tap: false
    });

    // Add zoom controls
    this.map.addControl(L.control.zoom({ position: "bottomright" }), {
      drawControl: true,
    });

    const osmTiles = L.tileLayer(
      "https://{s}.tile.osm.org/{z}/{x}/{y}.png",
      { attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors' }
    ).addTo(this.map);

    const googleSatTiles = L.tileLayer('http://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}',{
    maxZoom: 20,
    subdomains:['mt0','mt1','mt2','mt3']
    });

    const openTopoTiles = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
	    maxZoom: 17,
	    attribution: 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)'
    });

    const googleTerrainTiles = L.tileLayer('http://{s}.google.com/vt/lyrs=p&x={x}&y={y}&z={z}',{
      maxZoom: 20,
      subdomains:['mt0','mt1','mt2','mt3']
    });

    const googleStreetsTiles = L.tileLayer('http://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}',{
      maxZoom: 20,
      subdomains:['mt0','mt1','mt2','mt3']
    });

    const googleHybridTiles = L.tileLayer('http://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}',{
      maxZoom: 20,
      subdomains:['mt0','mt1','mt2','mt3']
    });

    L.control.layers (
      {
        'OSM': osmTiles,
        'Open topo.': openTopoTiles,
        'Google sat.': googleSatTiles,
        'Google terr.': googleTerrainTiles,
        'Google street': googleStreetsTiles,
        'Google hybr.': googleHybridTiles
      },
      undefined,
      {position: 'bottomright'}
    ).addTo(this.map);

    // set map setup as finished
    setTimeout(() => {
      this.mapBodyHasBeenSetup = true;
    });
  }

  // updates state
  setMiniMapState(): void {
    const zoom = this.map.getZoom(); // default grid precision

    let geoGridPrecision = zoom; // default grid precision

    //Set grid precision to approx zoom level + 2 .
    //Max geo grid precision is 12. Dont't step over that
    if (!zoom) {
      geoGridPrecision = 12; // 12 is max Elastic geoGridPrecision
    }
    else {
      geoGridPrecision = zoom + 2;
    }

    // set new search params + result
    // use global search params (without page) as a base
    let globalParams = { ...this.globalParams };
    delete globalParams.page;
    searchModule.setMiniMapSearch({
      ...globalParams,
      bbox: this.getCurrentBoundingBox(),
      mapq: true,
      ghp: geoGridPrecision
    });
  }

  // updates map from state
  setMiniMapFromState(): void {
    this.resetMiniMap();

    let resourceHitsCount = this.miniMapSearchResult.total?.value;
    let heatData = this.miniMapSearchResult.aggregations?.geogrid?.grids.buckets;
    let resources = this.miniMapSearchResult?.hits;

    if (resourceHitsCount <= this.markerThreshold) {
      this.setClusterMarkers(this.map, resources);
      this.showMarkerInfo = true;
    }
    else {
      this.setHeatMap(this.map, heatData);
      this.showMarkerInfo = false;
    }

    this.centerMiniMap();
  }

  // sets cluster markers
  setClusterMarkers(map: any, markerResources: any): void {
    this.clusterMarkers = new L.MarkerClusterGroup();

    markerResources.forEach((resource: any) => {
      if (!resource.data?.spatial) {
        return;
      }

      resource.data.spatial.forEach((spatial: any) => {
        let markerType: any = mapUtils.markerType.point;

        if (spatial.spatialPrecision || spatial.coordinatePrecision ) {
          markerType = mapUtils.markerType.approx;
        }

        if (spatial?.geopoint) {
          let marker = L.marker(
            new L.LatLng(spatial.geopoint.lat, spatial.geopoint.lon),
            { icon: mapUtils.getMarkerIconType(markerType.marker) }
          );

          marker.bindTooltip(resource.data.title.text, { keepInView: true, noWrap: false, offset: [0, 0] } as L.TooltipOptions);

          marker.on('click',function() {
            window.location.href = process.env.ARIADNE_PUBLIC_PATH+'resource/'+ resource.id;
          });

          this.clusterMarkers!.addLayer(marker);
        }
        else if (spatial?.polygon || spatial?.boundingbox) {
          // Create a new Wicket instance
          // DOCS: http://arthur-e.github.io/Wicket/doc/out/Wkt.Wkt.html
          const wkt = new Wkt.Wkt();
          // Read in any kind of WKT string
          let shape = spatial?.polygon || spatial?.boundingbox;
          wkt.read(shape);
          const feature = wkt.toObject({color: 'red'});

          // Get center of current polygon
          let polygonCenter = feature.getBounds().getCenter();

          let polygonMarker = L.marker(
            new L.LatLng(polygonCenter.lat, polygonCenter.lng),
            { icon: mapUtils.getMarkerIconType(markerType.shape) }
          );

          polygonMarker.bindTooltip(resource.data.title.text, { keepInView: true, noWrap: true, offset: [0, 0] } as L.TooltipOptions);

          polygonMarker.on('click',function() {
            window.location.href = process.env.ARIADNE_PUBLIC_PATH+'resource/'+ resource.id;
          });

          this.clusterMarkers!.addLayer(polygonMarker);
        }
        else {
          return;
        }
      });
    });

    map.addLayer(this.clusterMarkers);
  }

  // sets heatmap with geogrids from agg
  setHeatMap(map: any, heatPoints: any): void {
    let max = Math.max.apply(
      null,
      heatPoints?.map((hp: any) => hp.doc_count || 0)
    );

    let mapPoints: any[] = [];
    let points = [];

    heatPoints.forEach((hp: any) => {
      let decoded = Geohash.decode(hp["key"]);

      mapPoints.push([decoded.lat, decoded.lon, hp["doc_count"]]);
      points.push([decoded.lat, decoded.lon]);
    });

    this.heatMap = L.heatLayer(mapPoints, {
      radius: 10,
      max: max,
      maxZoom: 9,
      gradient: {
        "0": "Navy",
        "0.25": "Blue",
        "0.5": "Green",
        "0.75": "Yellow",
        "1": "Red",
      },
      //gradient: mapUtils.getGradient(1),
      minOpacity: 0.3,
    }).addTo(map);
  }

  // center map with correpsonding existings data
  centerMiniMap(): void {
    const miniMapSearchParams = { ...this.miniMapSearchParams };

    if (miniMapSearchParams?.bbox) {
      const bounds = miniMapSearchParams?.bbox.split(',');

      this.map.fitBounds(
        [
          [bounds[0],bounds[1]],
          [bounds[2],bounds[3]]
        ]
      );
    }
    else if (this.miniMapSearchResult.aggregations?.geogrid?.grids) {
      // Center to Result geogrids.
      let currentGeogridBuckets = this.miniMapSearchResult.aggregations?.geogrid?.grids?.buckets;

      if (Object.keys(currentGeogridBuckets).length) {
        let points: any[] = [];

        currentGeogridBuckets.forEach((hp: any) => {
          let decoded = Geohash.decode(hp["key"]);
          points.push([decoded.lat, decoded.lon]);
        });

        this.map.fitBounds(L.latLngBounds(points), {
          padding: L.point(10, 10),
        });

        return;
      }
      else if (this.miniMapSearchResult.hits.length === 1) {
        const points = mapUtils.getLatLngPointsForResource(this.miniMapSearchResult.hits[0].data);

        if (points.length) {
          this.map.fitBounds(L.latLngBounds(points), {
            padding: L.point(10, 10),
          });
        }
      }
    }
    else {
      this.map.fitWorld();
      this.map.setZoom(0);
    }
  }

  // clear heat and marker layers
  resetMiniMap(): void {
    // Reset draw layer
    if (this.drawLayer) {
      this.map.removeLayer(this.drawLayer);
      this.drawLayer = null;
    }

    // Reset heat layer
    if (this.heatMap) {
      this.map.removeLayer(this.heatMap);
      this.heatMap = null;
    }

    // Reset marker layer
    if (this.clusterMarkers) {
      this.map.removeLayer(this.clusterMarkers);
      this.clusterMarkers = null;
    }
  }

  // sync global search with mini map
  syncGlobalSearchWithMiniMap(path?: string): void {
    let params = { ...this.miniMapSearchParams };
    params.path = path;
    searchModule.setSearch(params);
  }

  // sync mini map with global search params
  syncMinimapWithGlobalSearchParams(): void {
    // use global search params (without page) as a base
    let newMiniMapSearchParams = { ...this.globalParams };
    delete newMiniMapSearchParams.page;

    // save new mini map state
    searchModule.setMiniMapSearch(newMiniMapSearchParams);
  }

  // get bounding box
  getCurrentBoundingBox(): string {
    const northWest = this.map.getBounds().getNorthWest();
    const southEast = this.map.getBounds().getSouthEast();

    // This might seem strange but wrap() doesn't work as intended.
    // Wrapping lat/lng still givs strange results.
    if (northWest.lat > 90 ) {
      northWest.lat = 90;
    }
    if (northWest.lng < -180 ) {
      northWest.lng = -180;
    }
    if (southEast.lat < -90 ) {
      southEast.lat = -90;
    }
    if (southEast.lng > 180 ) {
      southEast.lng = 180;
    }

    let latLng = [
      northWest.lat,
      northWest.lng,
      southEast.lat,
      southEast.lng
    ];

    return latLng.toString();
  }

  // set this.map map with values from vuex state
  @Watch('miniMapBodyOrSearchResultUpdates', { deep: true })
  onMiniMapBodyOrSearchResultUpdates() {
    // only update if both map body and results have value
    if (this.mapBodyHasBeenSetup && utils.objectIsNotEmpty(this.miniMapSearchResult)) {
      this.setMiniMapFromState();
      // first time setup for zoomend/dragend
      if (!this.mapBodyHasZoomendDragend) {
        this.map.on('zoomend dragend', this.setMiniMapState);
        this.mapBodyHasZoomendDragend = true;
      }
    }
  }

  // update mini map data when global result has updated
  @Watch('globalResult', { deep: true })
  onGlobalParamstUpdate() {
    // only sync if relevant props (aggregation filters, timeline) aren't already identical
    const propsToIgnore = ['page', 'sort', 'order', 'size'];
    if (!utils.objectEquals(this.globalParams, this.miniMapSearchParams, propsToIgnore)) {
      this.syncMinimapWithGlobalSearchParams();
    }
  }
}
</script>
