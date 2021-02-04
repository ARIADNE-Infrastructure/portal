<style>
.leaflet-touch .leaflet-control-layers-toggle {
    width: 30px;
    height: 30px;
}
</style>
<template>
  <div v-show="map" class="mb-lg">
    <!-- map -->
    <div ref="mapWrapper"></div>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Prop, Watch } from "vue-property-decorator";
import { search, general, resource } from "@/store/modules";

import * as L from "leaflet";
import Geohash from "latlon-geohash";
import utils from "@/utils/utils";

// Leaflet stuff
import "leaflet.heat";
import "leaflet.markercluster";
import "leaflet/dist/leaflet.css";

import "leaflet.markercluster/dist/MarkerCluster.css";
import "leaflet.markercluster/dist/MarkerCluster.Default.css";

// Leaflet Draw api
import "leaflet-draw";
import "leaflet-draw/dist/leaflet.draw.css";

import HelpTooltip from "@/component/Help/Tooltip.vue";

@Component({
  components: {
    HelpTooltip,
  },
})
export default class FilterSearchFullMap extends Vue {
  @Prop() title?: string;
  @Prop() height!: string;
  @Prop() noZoom?: boolean;
  @Prop() showInfo?: boolean;

  // Render cluster markers when this limit is reached
  markerThreshold: number = 500;

  map: any = null;
  clusterMarkers = null;
  heatMap = null;
  drawLayer = null;
  thisViewPort = null;
  debugToConsole = false;
  currentViewport = null;

  // ClusterMarkers - Custom style
  blueMarkerIcon = L.icon({
    iconUrl: `${this.assets}/leaflet/marker-icon-blue.png`,
    iconSize: [25, 41],
    iconAnchor: [12, 40],
    shadowUrl: `${this.assets}/leaflet/marker-shadow.png`,
    shadowSize: [41, 41],
    shadowAnchor: [12, 40],
  });

  mounted() {
    this.setupMapBody();
  }

  destroyed() {
    this.resetMap();
  }

  get currentResultState(): any {
    return search.getResult;
  }

  get resource(): any {
    return resource.getResource;
  }

  get params(): any {
    return search.getParams;
  }

  get assets(): string {
    return general.getAssetsDir;
  }

  get isLoading(): any {
    return general.getLoading;
  }

  @Watch("isLoading")
  loadingChange() {}

  @Watch("currentResultState")
  resultUpdateWatcher() {
    // mapq null means thar user has landed on Map without filters.
    // We need mapq to fetch correct map data from start.
    if(!this.params.mapq) {
      search.setSearch({
        mapq: true
      });
    } else {
      this.setMap();
    }
  }

  /**
   * Main function
   */
  async setMap() {

    this.lcl("<<<<<<<<<< SET MAP >>>>>>>>>>");

    this.resetMap();

    let geoBucketsCount = this.currentResultState.aggs?.geogrid?.buckets.length;
    let resourceHitsCount = this.currentResultState?.total?.value;
    this.currentViewport = this.currentResultState?.aggs?.viewport;

    this.lcl("BUCKETS COUNT: " + geoBucketsCount);
    this.lcl("RESOURCE COUNT: " + resourceHitsCount);
    this.lcl("CURRENT ZOOM: " + this.map.getZoom());
    this.lcl("CURRENT VIEWPORT:");
    this.lcl(this.currentViewport);

    if (resourceHitsCount <= this.markerThreshold) {
      this.setupClusterMarkers();

    } else {
      //this.setupClusterHeat();
      this.setupHeatMap();
    }

    // Undefined mapZoom means that user has landed on map directly without filters
    if (!this.map.getZoom()) {
      this.centerMap();
      // this.$router.push({ path: '/browse/where', query: { mapq: 'true', bbox: this.map.getBounds().toBBoxString() }});
    }

    if (this.thisViewPort) {
      this.map.removeLayer(this.thisViewPort);
      this.thisViewPort = null;
    }

    // For debug
    // this.showResultViewport();

    this.setupDrawCreated();
    this.setupOnMove();
  }

  async setupClusterHeat() {
    this.lcl("--- SET CLUSTER HEAT --- ");
    this.clusterMarkers = new L.MarkerClusterGroup();
    let currentHeatPoints = this.currentResultState.aggs?.geogrid?.buckets;
    let mapPoints = [];
    let points = [];

    currentHeatPoints.forEach((hp: any) => {

      let decoded = Geohash.decode(hp["key"]);
      mapPoints.push([decoded.lat, decoded.lon, hp["doc_count"]]);
      points.push([decoded.lat, decoded.lon]);

      let marker = L.marker(
        new L.LatLng(decoded.lat, decoded.lon),
        { icon: this.blueMarkerIcon }
      );

      this.clusterMarkers.addLayer(marker);

    });

    this.map.addLayer(this.clusterMarkers);

  }

  /**
   * Set markers
   */
  setMarker(heatpoint: any) {
    let decoded = Geohash.decode(heatpoint["key"]);

    // L.marker([decoded.lat, decoded.lon]).addTo(this.map)
    //   .bindPopup(decoded.lat + ' ' + decoded.lon + ' : ' + heatpoint['doc_count'] );

    var icon = new L.Icon.Default();
    icon.options.shadowSize = [0, 0];
    var marker = new L.Marker([decoded.lat, decoded.lon], { icon: icon })
      .addTo(this.map)
      .bindPopup(
        decoded.lat + " " + decoded.lon + " : " + heatpoint["doc_count"]
      );
  }

  /**
   * Creates clusters and markers and add to map as new layer.
   */
  async setupClusterMarkers() {
    this.lcl("--- SET CLUSTER MARKERS --- ");
    let resources = this.currentResultState.hits;

    this.clusterMarkers = new L.MarkerClusterGroup();
    let markerGroup = [];

    resources.forEach((resource: any) => {
      resource.data.spatial.forEach((spatial: any) => {
        let marker = L.marker(
          new L.LatLng(spatial.location.lat, spatial.location.lon),
          { icon: this.blueMarkerIcon }
        );
        marker.bindTooltip(resource.data.title);
        marker.bindPopup(this.getMarkerPopup(resource, spatial));
        this.clusterMarkers.addLayer(marker);
      });
    });

    this.map.addLayer(this.clusterMarkers);

  }

  /**
   * Build marker popup
   */
  getMarkerPopup(resource: any, spatial: any): string {

    let description = resource.data.description;
    if(description) {
      if (description?.length > 100) {
        description = description.slice(0, 100) + '...';
      }
      description = description + '<br><br>';
    } else {
      description = '';
    }

    let publishers = "<strong>Publisher:</strong> <br>";
    for (let currentPublisher of resource.data.publisher) {
      publishers += "&nbsp;" + currentPublisher.name + "<br>";
    }

    let popup =
      "<b>" + resource.data.title + "</b><br><br>" +
      description +
      publishers + "<br><br>" +
      " Lat: " + spatial.location.lat + " Lng: " + spatial.location.lon + "<br><br>" +
      '<a href="/resource/'+ resource.id +'">View resource</a>';

    return popup;
  }

  /**
   * Custom gradients for heatmap
   */
  getGradient(opacity: number) {
    let gradient = {};
    for (let i = 1; i <= 10; i++) {
      let key = Math.round(((opacity * i) / 10) * 100) / 100,
        val = Math.round((1.0 - i / 10) * 240);
      gradient[key] = `hsl(${val}, 90%, 50%)`;
    }
    return gradient;
  }

  /**
   * Setup heatmap with geogrids from aggs
   */
  async setupHeatMap() {
    this.lcl("--- SET HEAT MAP ---");

    let currentHeatPoints = this.currentResultState.aggs?.geogrid?.buckets;
    let max = Math.max.apply(
      null,
      currentHeatPoints.map((hp: any) => hp.doc_count || 0)
    );
    let mapPoints = [];
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
      //gradient: this.getGradient(1),
      minOpacity: 0.3,
    }).addTo(this.map);
  }


  /**
   * Event for drawing shapes on map
   */
  setupDrawCreated() {
    this.lcl("Draw layer");

    this.map.on("draw:created", (shape: any) => {

      // Remove draw layer from map if any.
      if (this.drawLayer) {
        this.map.removeLayer(this.drawLayer);
        this.drawLayer = null;
      }

      // Set drawn layer
      this.drawLayer = shape.layer;

      if (this.drawLayer === "rectangle") {
        //this.lcl("Rectangle drawn");
      }

      this.map.fitBounds(this.drawLayer.getBounds());

    });
  }

  /**
   * Setup moveEnd event
   * Each time the map is moved, fetch new data to re-render map layers with new boundingbox
   */
  setupOnMove() {

    this.lcl("--- SETUP ON-MOVE ---");

    let geoGridPrecision = this.map.getZoom(); // default grid precision

    //Set grid precision to approx zoom level + 2 .
    //Max geo grid precision is 12. Dont't step over that
    if (!this.map.getZoom()) {
      geoGridPrecision = 12; // 12 is max Elastic geoGridPrecision
    } else {
      geoGridPrecision = this.map.getZoom() + 2;
    }

    this.map.once("moveend", (ev: any) => {
      // Fetch search result with current boundingbox and set to store.
      if (this.getCurrentBoundingBox()) {
        let currentBBox = this.getCurrentBoundingBox();
        search.setSearch({
          bbox: currentBBox,
          size: this.markerThreshold,
          ghp: geoGridPrecision,
          zoomLevel: this.map.getZoom(),
          mapCenter: this.map.getCenter().lat + "," + this.map.getCenter().lng,
          clear: false,
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
    mapRef.innerHTML = `<div id="map" style="height:${this.height};"></div>`;

    this.map = L.map("map", {
      zoomControl: false,
      scrollWheelZoom: !this.noZoom,
      wheelDebounceTime: 300,
      debounceMoveend: true,
      detectRetina: true,
      tap: false // This needs to be here for Safari users. If not the the marker popups don't work! TODO: test for tabs and smartphones! 
    });

    // Setup and add draw controls
    // Toolips for buttons
    L.drawLocal.draw.toolbar.buttons.polygon = "Draw a polygon and zoom in";
    L.drawLocal.draw.toolbar.buttons.rectangle = "Draw a rectangle and zoom in";

    var drawnItems = new L.FeatureGroup();
    this.map.addLayer(drawnItems);
    var drawControl = new L.Control.Draw({
      position: "bottomright",
      edit: false,
      draw: {
        // polygon: {
        //   allowIntersection: false,
        //   showArea: true,
        //   metric: ["km"],
        // },
        marker: false,
        polyline: false,
        circle: false,
        circlemarker: false,
        polygon: false,
      },
    });
    this.map.addControl(drawControl);

    // Add zoom controls
    this.map.addControl(L.control.zoom({ position: "bottomright" }), {
      drawControl: true,
    });

    var osmTiles = L.tileLayer(
      "https://{s}.tile.osm.org/{z}/{x}/{y}.png",
      { attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors' }
    ).addTo(this.map);

    var googleSatTiles = L.tileLayer('http://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}',{
    maxZoom: 20,
    subdomains:['mt0','mt1','mt2','mt3']
    });

    var openTopoTiles = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
	    maxZoom: 17,
	    attribution: 'Map data: &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, <a href="http://viewfinderpanoramas.org">SRTM</a> | Map style: &copy; <a href="https://opentopomap.org">OpenTopoMap</a> (<a href="https://creativecommons.org/licenses/by-sa/3.0/">CC-BY-SA</a>)'
    });

    var googleTerrainTiles = L.tileLayer('http://{s}.google.com/vt/lyrs=p&x={x}&y={y}&z={z}',{
      maxZoom: 20,
      subdomains:['mt0','mt1','mt2','mt3']
    });
    var googleStreetsTiles = L.tileLayer('http://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}',{
      maxZoom: 20,
      subdomains:['mt0','mt1','mt2','mt3']
    });
    var googleHybridTiles = L.tileLayer('http://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}',{
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
      null,
      {position: 'bottomright'}
    ).addTo(this.map);

  }


  /**
   * Center map with correpsonding existings data
   */
  centerMap() {

    this.lcl("--- CENTER MAP ---");

    let currentGeogridBuckets = this.currentResultState.aggs?.geogrid?.buckets;

    if (this.currentViewport.bounds) {
      // Center to Result viewport.
      this.map.fitBounds(
        [
          [
            this.currentViewport.bounds.bottom_right.lat,
            this.currentViewport.bounds.bottom_right.lon,
          ],
          [
            this.currentViewport.bounds.top_left.lat,
            this.currentViewport.bounds.top_left.lon,
          ],
        ],
        {
          padding: L.point(2, 2),
        }
      );
    } else {
      // Center to Result geogrids.
      let currentGeogridBuckets = this.currentResultState.aggs?.geogrid
        ?.buckets;

      if (Object.keys(currentGeogridBuckets).length) {
        let points = [];
        currentGeogridBuckets.forEach((hp: any) => {
          let decoded = Geohash.decode(hp["key"]);
          points.push([decoded.lat, decoded.lon]);
        });
        this.map.fitBounds(L.latLngBounds(points), {
          padding: L.point(2, 2),
        });
      } else {
        // Wo don't have geogrids or viewport. Center to whole world.
        this.map.fitWorld();
        this.map.setZoom(2);
      }
    }
  }

  /**
   * Get current boundingbox set by map move events
   */
  getCurrentBoundingBox() {
    let bounds = new L.latLngBounds(
      this.map.getBounds().getSouthWest().wrap(),
      this.map.getBounds().getNorthEast().wrap()
    );
    return bounds.toBBoxString();
  }


  /**
   * Clear heat and marker layers
   */
  resetMap() {

    this.lcl("--- RESET MAP ---");

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
      this.clusterMarkers = [];
    }

  }

  /**
   * Mainly for debuging
   */
  showResultViewport() {

    this.lcl('--- SHOW VIEWPORT ---');

    if (this.currentViewport.bounds) {
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

  /**
   * Local console logger
   */
  lcl(logMsg) {
    if(this.debugToConsole) {
      console.log( logMsg );
    }
  }

}
</script>
