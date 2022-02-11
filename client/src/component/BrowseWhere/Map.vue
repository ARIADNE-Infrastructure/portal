<template>
  <div
    v-show="map"
    class="mb-lg absolute left-0 w-full"
  >
    <div
      class="absolute w-full text-center text-white bg-red p-md z-1001 transition-opacity duration-300"
      :class="{ 'opacity-0': !getIsAboveMaxNativeZoom }"
    >
      Please switch to another map view for a more optimal viewing experience.
    </div>

    <!-- map -->
    <div ref="mapWrapper"></div>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Watch } from "vue-property-decorator";
import { searchModule, generalModule } from "@/store/modules";
import { LoadingStatus } from "@/store/modules/General";

import * as L from "leaflet";
import Geohash from "latlon-geohash";
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

// typing for available tile layers
interface tileLayer {
  [key: string]: L.TileLayer,
}

@Component({
  components: {
    HelpTooltip,
  },
})
export default class BrowseWhereMap extends Vue {
  // Render cluster markers when this limit is reached
  markerThreshold: number = 500;

  map: any = null;
  clusterMarkers: L.MarkerClusterGroup | null = null;
  heatMap: L.HeatLayer | null = null;
  drawLayer: any = null;
  thisViewPort: L.Rectangle | null = null;
  currentViewport: any = null;

  // available tile layers
  tileLayer: tileLayer = {
    'OSM': L.tileLayer('https://{s}.tile.osm.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors',
      maxNativeZoom: 19,
      maxZoom: 20,
      noWrap: true,
    }),

    'Open topo.': L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
      attribution: 'Map data: &copy;'
        + ' <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        + ' contributors, <a href="http://viewfinderpanoramas.org">SRTM</a>'
        + ' | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a>'
        + ' (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)',

      maxNativeZoom: 17,
      maxZoom: 20,
    }),

    'Google sat.': L.tileLayer('http://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
      maxNativeZoom: 20,
      maxZoom: 20,
      subdomains:['mt0','mt1','mt2','mt3'],
    }),

    'Google terr.': L.tileLayer('http://{s}.google.com/vt/lyrs=p&x={x}&y={y}&z={z}', {
      maxNativeZoom: 20,
      maxZoom: 20,
      subdomains:['mt0','mt1','mt2','mt3'],
    }),

    'Google street': L.tileLayer('http://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
      maxNativeZoom: 20,
      maxZoom: 20,
      subdomains:['mt0','mt1','mt2','mt3']
    }),

    'Google hybr.': L.tileLayer('http://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}', {
      maxNativeZoom: 20,
      maxZoom: 20,
      subdomains:['mt0','mt1','mt2','mt3']
    }),
  };

  // current tile layer
  currentTileLayer: L.TileLayer = this.tileLayer.OSM;

  // current map zoom
  currentZoom: number = 0;

  mounted() {
    this.setupMapBody();
  }

  destroyed() {
    this.resetMap();
  }

  get currentResultState(): any {
    return searchModule.getResult;
  }

  get params(): any {
    return searchModule.getParams;
  }

  get isLoading(): any {
    return generalModule.getIsLoading;
  }

  @Watch("isLoading")
  loadingChange() {}

  @Watch("currentResultState")
  resultUpdateWatcher() {
    this.setMap();
  }

  /**
   * Main function
   */
  async setMap() {
    //debugger;
    this.resetMap();

    let resourceHitsCount = this.currentResultState?.total?.value;
    this.currentViewport = this.currentResultState?.aggs?.viewport?.thisBounds;
    let inMarkerView: boolean = false;

    if (resourceHitsCount <= this.markerThreshold) {
      inMarkerView = !inMarkerView;
      this.setupClusterMarkers();
    } else {
      this.setupHeatMap();
    }

    this.$emit('markerViewUpdate', inMarkerView);

    // Undefined mapZoom means that user has landed on map directly without filters
    if (!this.map.getZoom()) {
      this.centerMap();
      // this.$router.push({ path: '/browse/where', query: { mapq: 'true', bbox: this.map.getBounds().toBBoxString() }});
    }

    if (this.thisViewPort) {
      this.map.removeLayer(this.thisViewPort);
      this.thisViewPort = null;
    }

    // Render current viewport
    //this.showResultViewport();

    this.setupDrawCreated();
    this.setupOnMove();

    this.currentZoom = this.map?.getZoom() ?? 0;
  }

  /**
   * Set markers
   */
  setMarker(heatpoint: any): void {
    let decoded = Geohash.decode(heatpoint["key"]);

    // L.marker([decoded.lat, decoded.lon]).addTo(this.map)
    //   .bindPopup(decoded.lat + ' ' + decoded.lon + ' : ' + heatpoint['doc_count'] );

    const icon = new L.Icon.Default();
    icon.options.shadowSize = [0, 0];

    new L.Marker([decoded.lat, decoded.lon], { icon: icon })
      .addTo(this.map)
      .bindPopup(
        decoded.lat + " " + decoded.lon + " : " + heatpoint["doc_count"]
      );
  }


  /**
   * Creates clusters and markers and add to map as new layer.
   */
  async setupClusterMarkers() {

    let resources = this.currentResultState.hits;
    this.clusterMarkers = new L.MarkerClusterGroup();

    resources.forEach((resource: any) => {
      resource.data.spatial.forEach((spatial: any) => {

        let markerType: any = mapUtils.markerType.point;
        if(spatial.spatialPrecision || spatial.coordinatePrecision ) {
          markerType = mapUtils.markerType.approx;
        }

        // console.log( markerType );

        if (spatial?.geopoint) {

          let marker = L.marker(
            new L.LatLng(spatial.geopoint.lat, spatial.geopoint.lon),
            { icon: mapUtils.getMarkerIconType(markerType.marker) }
          );

          marker.bindTooltip(resource.data.title.text);
          marker.bindPopup(this.getMarkerPopup(resource));

          this.clusterMarkers!.addLayer(marker);

        } else if( spatial?.polygon || spatial?.boundingbox  ) {

          // Create a new Wicket instance
          // DOCS: http://arthur-e.github.io/Wicket/doc/out/Wkt.Wkt.html
          const wkt = new Wkt.Wkt();
          // Read in any kind of WKT string
          let shape = spatial?.polygon || spatial?.boundingbox;
          wkt.read(shape);

          const feature = wkt.toObject({ color: 'red' });

          // Add polygon to map, if needed
          //this.clusterMarkers.addLayer(feature);

          // Get center of current polygon
          let polygonCenter = feature.getBounds().getCenter();
          //console.log( markerType.shape );
          let polygonMarker = L.marker(
            new L.LatLng(polygonCenter.lat, polygonCenter.lng),
            { icon: mapUtils.getMarkerIconType(markerType.shape) }
          );

          polygonMarker.bindTooltip(resource.data.title.text);
          polygonMarker.bindPopup(this.getMarkerPopup(resource));

          this.clusterMarkers!.addLayer(polygonMarker);

        } else {
          // Not Geopoint or Polygon
          return;

        }

      });
    });

    this.map.addLayer(this.clusterMarkers);

  }

  // Render cluster markers when this limit is reached
  get getIsAboveMaxNativeZoom(): boolean {
    const maxNativeZoom: number = this.currentTileLayer.options.maxNativeZoom ?? 0;

    return this.currentZoom > maxNativeZoom ? true : false;
  }

  /**
   * Build marker popup
   */
  getMarkerPopup(resource: any): string {

    let title = "<p><strong>"+resource.data.title.text+"</strong></p>";
    let description = resource.data.description?.text;
    let resourceLocationsLatLon = "";
    let resourceLocation = "";
    // @ts-ignore
    let resourcePage = '<p><a href="'+process.env.ARIADNE_PUBLIC_PATH+'resource/'+ resource.id +'">View resource</a></p>';
    let publishers = "";

    // Resource description
    if(description) {
      if (description?.length > 100) {
        description = description.slice(0, 100) + '...';
      }
    } else {
      description = "";
    }
    description = "<p>"+description+"</p>"

    // Resource publishers
    for (let currentPublisher of resource.data.publisher) {
      publishers += currentPublisher.name + "<br>";
    }
    publishers = "<p><strong>Publisher:</strong><br/>"+publishers+"</p>";

    // Resource location
    for (let resourceLocations of resource.data.spatial) {
      if(resourceLocations.geopoint) {
        resourceLocationsLatLon += resourceLocations.geopoint.lat + ":" + resourceLocations.geopoint.lon + "<br>";
      }
    }
    if(!resourceLocationsLatLon) {
      resourceLocation = '<p><strong>Resource location:</strong><br>Resource location is a geo-shape, see details on resource page.</p>';
    } else {
      resourceLocation = "<p><strong>Resource location:</strong><br>" + resourceLocationsLatLon + "</p>";
    }

    return title + description + publishers + resourceLocation + resourcePage;

  }

  /**
   * Setup heatmap with geogrids from aggs
   */
  async setupHeatMap() {

    let currentHeatPoints = this.currentResultState.aggs?.geogrid?.grids.buckets;
    let max = Math.max.apply(
      null,
      currentHeatPoints.map((hp: any) => hp.doc_count || 0)
    );

    let mapPoints: any[] = [];
    let points = [];

    currentHeatPoints.forEach((hp: any) => {
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
    }).addTo(this.map);
  }


  /**
   * Event for drawing shapes on map
   */
  setupDrawCreated() {

    this.map.on("draw:created", (shape: any) => {

      // Remove draw layer from map if any.
      if (this.drawLayer) {
        this.map.removeLayer(this.drawLayer);
        this.drawLayer = null;
      }

      // Set drawn layer
      this.drawLayer = shape.layer;
      this.map.fitBounds(this.drawLayer.getBounds());

    });
  }

  /**
   * Setup moveEnd event
   * Each time the map is moved, fetch new data to re-render map layers with new boundingbox
   */
  setupOnMove() {

    let geoGridPrecision = this.map.getZoom(); // default grid precision

    // Set grid precision to approx zoom level + 2 .
    // Max geo grid precision is 12. Dont't step over that
    if (!this.map.getZoom()) {
      geoGridPrecision = 12; // 12 is max Elastic geoGridPrecision
    } else {
      geoGridPrecision = this.map.getZoom() + 2;
    }

    this.map.once("moveend", () => {
      // Fetch search result with current boundingbox and set to store.
      if (this.getCurrentBoundingBox()) {
        let currentBBox = this.getCurrentBoundingBox();
        searchModule.setSearch({
          bbox: currentBBox,
          size: this.markerThreshold,
          ghp: geoGridPrecision,
          clear: false,
          loadingStatus: LoadingStatus.Background,
        });
      }
    });
  }

  /**
   * Sets up map skeleton
   */
  async setupMapBody() {
    // setup map div/css layer. Height is a prop set in BrowseWhere.vue
    let mapRef: any = this.$refs.mapWrapper;
    mapRef.innerHTML = `<div id="map" style="height: calc(100vh - 12rem)"></div>`;

    this.map = L.map("map", {
      zoomControl: false,
      scrollWheelZoom: true,
      wheelDebounceTime: 300,
      worldCopyJump: true,
      maxBounds: L.latLngBounds([-90,-180],[90,180]),
      tap: false // This needs to be here for Safari users. If not the the marker popups don't work! TODO: test for tabs and smartphones!
    });

    // Setup and add draw controls
    // Toolips for buttons
    L.drawLocal.draw.toolbar.buttons.polygon = "Draw a polygon and zoom in";
    L.drawLocal.draw.toolbar.buttons.rectangle = "Draw a rectangle and zoom in";

    const drawnItems = new L.FeatureGroup();
    this.map.addLayer(drawnItems);
    const drawControl = new L.Control.Draw({
      position: "bottomright",

      draw: {
        polyline: {
           allowIntersection: false,
           metric: true,
        },
        marker: false,
        circle: false,
        circlemarker: false
      },

    });
    this.map.addControl(drawControl);

    // Add zoom controls
    this.map.addControl(L.control.zoom({ position: "bottomright" }), {
      drawControl: true,
    });

    // Add initial (OSM) tile layer to map
    this.currentTileLayer.addTo(this.map);

    // Add add tile layers to layer picker
    L.control.layers(this.tileLayer, undefined, {position: 'bottomright'}).addTo(this.map);

    // update current tile layer variable on change
    this.map.on('baselayerchange', (newLayer: L.LayersControlEvent) => {
      const newLayerName: string = newLayer.name;
      const layers: any = this.tileLayer;

      this.currentTileLayer = layers[newLayerName];
    });
  }


  /**
   * Center map with correpsonding existings data
   */
  centerMap() {

    if( this.params?.bbox ) {

      const bounds = this.params?.bbox.split(',');
      this.map.fitBounds(
        [
          [bounds[0],bounds[1]],
          [bounds[2],bounds[3]]
        ]
      );

    } else if(this.currentResultState.aggs?.geogrid) {
      // Center to Result geogrids.
      let currentGeogridBuckets = this.currentResultState.aggs?.geogrid?.grids?.buckets;

      if (Object.keys(currentGeogridBuckets).length) {
        let points: any[] = [];
        currentGeogridBuckets.forEach((hp: any) => {
          let decoded = Geohash.decode(hp["key"]);
          points.push([decoded.lat, decoded.lon]);
        });
        this.map.fitBounds(L.latLngBounds(points), {
          padding: L.point(10, 10),
        });
      }

    } else {
      this.map.fitWorld();
      this.map.setZoom(2);
    }
  }

  /**
   * Get current boundingbox set by map move events
   * Returning coordinates as: upperLeft.lat, upperLeft.lng, lowerRight.lat, lowerRight.lng
   * NOTICE: L.latLngBounds does NOT return the coordinates as upperLeft, lowerRight!!
   */
  getCurrentBoundingBox() {

    const northWest = this.map.getBounds().getNorthWest();
    const southEast = this.map.getBounds().getSouthEast();

    // This might seem strange but wrap() doesn't work as intended.
    // Wrapping lat/lng still givs strange results.

    if(northWest.lat > 90 ) {
      northWest.lat = 90;
    }
    if(northWest.lng < -180 ) {
      northWest.lng = -180;
    }
    if(southEast.lat < -90 ) {
      southEast.lat = -90;
    }
    if(southEast.lng > 180 ) {
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


  /**
   * Clear heat and marker layers
   */
  resetMap() {

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

  /**
   * Mainly for debuging
   */
  showResultViewport() {

    if (this.currentViewport?.bounds) {
      this.thisViewPort = L.rectangle(
        [
          [
            this.currentViewport.bounds.bottom_right.lat,
            this.currentViewport.bounds.bottom_right.lon,
          ],
          [
            this.currentViewport.bounds.top_left.lat,
            this.currentViewport.bounds.top_left.lon,
          ]
        ],
        {
          fillColor: "grey",
          opacity: 0.1,
        }
      );
      this.map.addLayer(this.thisViewPort);
    }

  }

}


</script>
