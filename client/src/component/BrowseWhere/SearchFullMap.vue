<style>
.leaflet-zoom-anim .leaflet-zoom-animated {
  transition: 2s;
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

// Heatmap stuff
import "heatmap.js";
import "leaflet-heatmap";
import HeatmapOverlay from "leaflet-heatmap/leaflet-heatmap.js";

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
  heatmapLayer = null;
  clusterMarkers = null;
  hasMoved = false;
  heatMap = null;
  drawLayer = null;
  thisViewPort = null;

  resourcesWithoutSpatialData = [];

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
    this.setMap();
  }

  /**
   * Main function
   */
  async setMap() {
    console.log("<<<<<<<<<< SET MAP >>>>>>>>>>");
    this.resetMap();

    let geoBucketsCount = this.currentResultState.aggs?.geogrid?.buckets.length;
    let resourceHitsCount = this.currentResultState?.total?.value;
    let currentViewPort = this.currentResultState?.aggs?.viewport;

    console.log("BUCKETS COUNT: " + geoBucketsCount);
    console.log("RESOURCE COUNT: " + resourceHitsCount);
    console.log("CURRENT ZOOM: " + this.map.getZoom());
    console.log("CURRENT VIEWPORT:");
    console.log(currentViewPort);

    if (resourceHitsCount <= this.markerThreshold) {
      this.setupClusterMarkers();
    } else {
      this.setupHeatMap();
    }

    // Undefined mapZoom means that user has landed on map directly from bookmark or shared url
    if (!this.map.getZoom()) {
      this.centerMap();
    }

    if (this.thisViewPort) {
      this.map.removeLayer(this.thisViewPort);
      this.thisViewPort = null;
    }

    //this.map.setZoom(3);
    //this.map.setZoom(this.map.getZoom());

    // this.map.fitBounds(
    //   [
    //     [currentViewPort.bounds.bottom_right.lat, currentViewPort.bounds.bottom_right.lon],
    //     [currentViewPort.bounds.top_left.lat, currentViewPort.bounds.top_left.lon]
    //   ],
    //   {
    //     animate: true,
    //     duration: 2
    //   }
    // );
    this.showResultViewport();
    this.setupDrawCreated();
    this.setupOnMove();

    console.log("resourcesWithoutSpatialData: ");
    console.log(this.resourcesWithoutSpatialData);
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
    console.log("--- SET CLUSTER MARKERS --- ");
    let resources = this.currentResultState.hits;

    this.clusterMarkers = new L.MarkerClusterGroup();
    let markerGroup = [];

    resources.forEach((resource: any) => {
      if (resource.data.spatial != "undefined") {
        // Not all resources have spatial data. TLook for spatial data first.
        resource.data.spatial.forEach((spatial: any) => {
          // Not all resources have spatials
          if (spatial.location) {
            let marker = L.marker(
              new L.LatLng(spatial.location.lat, spatial.location.lon),
              { icon: this.blueMarkerIcon }
            );
            marker.bindTooltip(resource.data.title);
            marker.bindPopup(this.getMarkerPopup(resource, spatial));
            this.clusterMarkers.addLayer(marker);
          } else {
            console.log("ADDED NO SPATIAL RESOURCE ");
            this.resourcesWithoutSpatialData.push(resource);
          }
        });
      } else {
        // This means that map view has hits but the resource dosn't have spatial data.
        //console.log( 'ADDED NO SPATIAL RESOURCE ' );
        //this.resourcesWithoutSpatialData.push( resource );
      }
    });

    this.map.addLayer(this.clusterMarkers);

    // let currentViewPort = this.currentResultState?.aggs?.viewport;
    // this.map.fitBounds(
    //   [
    //     [currentViewPort.bounds.bottom_right.lat, currentViewPort.bounds.bottom_right.lon],
    //     [currentViewPort.bounds.top_left.lat, currentViewPort.bounds.top_left.lon]
    //   ],
    //   {
    //     animate: true,
    //     duration: 2
    //   }
    // );
  }

  /**
   * Build marker popup
   */
  getMarkerPopup(resource: any, spatial: any): string {
    console.log(resource.data);
    let description = resource.data.description;
    if (description?.length > 100) {
      description = description.slice(0, 100) + "...";
    }

    let publishers = "<strong>Publisher:</strong> <br>";
    for (let currentPublisher of resource.data.publisher) {
      publishers += "&nbsp;" + currentPublisher.name + "<br>";
    }

    let popup =
      "<b>" +
      resource.data.title +
      "</b><br><br>" +
      description +
      "<br><br>" +
      publishers +
      "<br><br>" +
      " Lat: " +
      spatial.location.lat +
      " Lon: " +
      spatial.location.lon +
      "<br><br>" +
      '<a href="/resource/' +
      resource.id +
      '">View resource</a>';

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
    console.log("--- SET HEAT MAP ---");

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
      //gradient: this.getGradient(0.4),
      minOpacity: 0.3,
    }).addTo(this.map);

    // Center and zoom map accordingly
    if (search.hasParams) {
      // This fires only when user lands on map for the first time, without search filters and no uri params
      // If so, center and zoom arround points
      // this.map.fitBounds(L.latLngBounds(points), {
      //   padding: L.point(1, 1)
      // });
    }
  }

  /**
   * Event for drawing shapes on map
   */
  setupDrawCreated() {
    console.log("Draw layer");

    this.map.on("draw:created", (shape: any) => {
      // Remove draw layer from map if any.
      if (this.drawLayer) {
        this.map.removeLayer(this.drawLayer);
        this.drawLayer = null;
      }

      this.drawLayer = shape.layer;

      if (shape.layerType === "rectangle") {
        console.log("Rectangle drawn");
        console.log(shape.layer.getLatLngs());
        // Get shape lat and lng
      } else if (shape.layerType === "polygon") {
        console.log("Polygon drawn");
        console.log(shape.layer.getLatLngs());
      }

      this.map.fitBounds(shape.layer.getBounds(), {
        padding: L.point(1, 1),
      });
      //console.log( shape.layer.getBounds() );

      // Do whatever else you need to. (save to db; add to map etc)
      this.map.addLayer(this.drawLayer);
    });
  }

  /**
   * Setup moveEnd event
   * Each time the map is moved, fetch new data to re-render map layers with new boundingbox
   */
  setupOnMove() {
    console.log("--- SETUP ON-MOVE ---");
    this.hasMoved = true;
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
          clear: false,
          ghp: geoGridPrecision,
          size: this.markerThreshold,
          zoomLevel: this.map.getZoom(),
          mapCenter: this.map.getCenter().lat + "," + this.map.getCenter().lng,
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
        polygon: {
          allowIntersection: false,
          showArea: true,
          metric: ["km"],
        },
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
    // http://otile1.mqcdn.com/tiles/1.0.0/map/${z}/${x}/${y}.png
    // https://{s}.tile.osm.org/{z}/{x}/{y}.png
    L.tileLayer("https://{s}.tile.osm.org/{z}/{x}/{y}.png", {
      attribution:
        '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(this.map);
  }

  centerMap() {
    console.log("--- CENTER MAP ---");

    let currentGeogridBuckets = this.currentResultState.aggs?.geogrid?.buckets;
    let currentViewport = this.currentResultState.aggs?.viewport;

    if (Object.keys(currentViewport).length) {
      // Center to Result viewport.
      console.log("Fit map to ViewPort");
      console.log(currentViewport);
      this.map.fitBounds(
        [
          [
            currentViewport.bounds.bottom_right.lat,
            currentViewport.bounds.bottom_right.lon,
          ],
          [
            currentViewport.bounds.top_left.lat,
            currentViewport.bounds.top_left.lon,
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
        console.log("Fit map to Geogrid");

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
        console.log("Fit map to World");
        this.map.fitWorld();
        this.map.setZoom(2);
      }
    }
  }

  /**
   * Center map arround current heats
   */
  centerHeatMap() {
    console.log("--- CENTER MAP ---");
    let currentHeatPoints = this.currentResultState.aggs?.geogrid?.buckets;
    let points = [];
    currentHeatPoints.forEach((hp: any) => {
      let decoded = Geohash.decode(hp["key"]);
      points.push([decoded.lat, decoded.lon]);
    });

    this.map.fitBounds(L.latLngBounds(points), {
      padding: L.point(5, 5),
    });
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

  getResourceFromCurrentResult(resourceId) {
    return { title: `ResoruceId: ${resourceId}` };
  }

  getTooltip(resource) {
    return "<div>This tooltip</div>";
  }

  /**
   * Clear heat and marker layers
   */
  resetMap() {
    console.log("--- RESET MAP ---");

    // Reset draw layer
    if (this.drawLayer) {
      this.map.removeLayer(this.drawLayer);
      this.drawLayer = null;
    }

    // Reset heat layer
    if (this.heatmapLayer) {
      this.map.removeLayer(this.heatmapLayer);
      this.heatmapLayer = null;
    }

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

  showResultViewport() {

    let currentViewPort = this.currentResultState?.aggs?.viewport;
    if (Object.keys(currentViewPort).length) {

      //14.879865646362305,36.57424997928846,39.225568771362305,49.24859054319687
      this.thisViewPort = L.rectangle(
        //this.map.getBounds(),
        [
          [
            currentViewPort.bounds.bottom_right.lat,
            currentViewPort.bounds.bottom_right.lon,
          ],
          [
            currentViewPort.bounds.top_left.lat,
            currentViewPort.bounds.top_left.lon,
          ],
          //[14.879865646362305,36.57424997928846],
          //[39.225568771362305,49.24859054319687]
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
