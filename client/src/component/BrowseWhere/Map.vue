<template>
  <div
    v-show="hasMapData"
    class="mb-lg absolute left-0 w-full ariadne-map"
  >
    <div
      class="absolute w-full text-center text-white bg-red p-md z-10 transition-opacity duration-300"
      :class="{ 'opacity-0': !getIsAboveMaxNativeZoom }"
    >
      Please switch to another map view for a more optimal viewing experience.
    </div>

    <!-- map -->
    <div id="mapWrapper"></div>
  </div>
</template>

<script setup lang="ts">
import { watch, onMounted, onUnmounted } from 'vue';
import { $ref, $computed, $$ } from 'vue/macros';
import { onBeforeRouteLeave } from 'vue-router';
import { searchModule, generalModule } from "@/store/modules";
import { LoadingStatus } from "@/store/modules/General";
import utils from '@/utils/utils';

import * as L from "leaflet";
import Geohash from "latlon-geohash";
import "leaflet.heat";
import "leaflet.markercluster";
import "leaflet-draw";
import 'wicket/wicket-leaflet';
import Wkt from 'wicket';

// typing for available tile layers
interface tileLayer {
  [key: string]: L.TileLayer,
}

let mapObj: any = null;
let isUnmounted: boolean = false;
let hasMapData: boolean = $ref(false);
const emit = defineEmits(['markerViewUpdate']);
const markerTypes = utils.getMarkerTypes(generalModule);

// Render cluster markers when this limit is reached
const markerThreshold: number = 500;
let clusterMarkers: L.MarkerClusterGroup | null = null;
let heatMap: L.HeatLayer | null = null;
let drawLayer: any = null;
let currentMapBounds: any = null;
let first: boolean = true;

// available tile layers
const allTileLayers: tileLayer = utils.getTileLayers(L, true, true);

// current tile layer
let currentTileLayer: L.TileLayer = allTileLayers.OSM;

// current map zoom
let currentZoom: number = 0;

onMounted(() => setupMapBody());

onUnmounted(() => {
  resetMap();
  mapObj?.off();
  mapObj = null;
  isUnmounted = true;
});

const currentResultState = $computed(() => searchModule.getResult);
const params = $computed(() => searchModule.getParams);

/**
 * Main function
 */
const setMap = async () => {
  if (isUnmounted) {
    return;
  }
  resetMap();
  let resourceHitsCount = currentResultState?.total?.value;
  let inMarkerView: boolean = false;

  if (first) {
    first = false;
    if (params?.bbox && !currentMapBounds) {
      centerMap();
    }
  }

  if (resourceHitsCount <= markerThreshold) {
    inMarkerView = !inMarkerView;
    setupClusterMarkers();
  } else {
    setupHeatMap();
  }

  // Tell ui that map is in makrer view to show info abut the different marker icons
  emit('markerViewUpdate', inMarkerView);

  // Undefined mapZoom means that user has landed on map directly without filters
  if (!mapObj.getZoom()) {
    centerMap();
  }

  setupDrawCreated();
  setupOnMove();

  currentZoom = mapObj?.getZoom() ?? 0;
}

/**
 * Creates clusters and markers and add to map as new layer.
 */
const setupClusterMarkers = async () => {
  let resources = currentResultState.hits;
  clusterMarkers = new L.MarkerClusterGroup();

  resources.forEach((resource: any) => {

    let resourceTitle = 'No title';
    if(resource.data?.title?.text) {
      resourceTitle = resource.data?.title?.text
    }

    resource.data.spatial.forEach((spatial: any) => {

      let markerType: any = markerTypes.point;
      if(spatial.spatialPrecision || spatial.coordinatePrecision ) {
        markerType = markerTypes.approx;
      }

      if (spatial?.geopoint) {

        let marker = L.marker(
          new L.LatLng(spatial.geopoint.lat, spatial.geopoint.lon),
          { icon: getMarkerIconType(markerType.marker) }
        );

        marker.bindTooltip(resourceTitle, { direction: 'top', offset: [0, -35] });
        marker.bindPopup(getMarkerPopup(resource));

        clusterMarkers!.addLayer(marker);

      } else if( spatial?.polygon || spatial?.boundingbox ) {

        // Create a new Wicket instance
        // DOCS: http://arthur-e.github.io/Wicket/doc/out/Wkt.Wkt.html
        const wkt = new Wkt.Wkt();
        // Read in any kind of WKT string
        let shape = spatial?.polygon || spatial?.boundingbox;
        wkt.read(shape);

        const feature = wkt.toObject({ color: 'red' });

        // Add polygon to map, if needed
        //this.clusterMarkers.addLayer(feature);

        // Get center of current polygon
        let polygonCenter;
        if (feature.getBounds) {
          polygonCenter = feature.getBounds().getCenter();
        } else {
          polygonCenter = L.latLng({ lat: feature._latlng.lat, lng: feature._latlng.lat });
        }
        let polygonMarker = L.marker(
          new L.LatLng(polygonCenter.lat, polygonCenter.lng),
          { icon: getMarkerIconType(markerType.shape) }
        );

        polygonMarker.bindTooltip(resourceTitle, { direction: 'top', offset: [0, -35] });
        polygonMarker.bindPopup(getMarkerPopup(resource));

        clusterMarkers!.addLayer(polygonMarker);

      } else {
        // Not Geopoint or Polygon
        return;

      }

    });
  });

  // NOTICE: As soon as we have Elastic >= 7.14 we can pick bounds from geobounds aggregation
  // Current version is 7.4 and it doesn't support geo_bounds for geoshapes
  mapObj.addLayer(clusterMarkers);
  currentMapBounds = clusterMarkers.getBounds();

}

  // Render cluster markers when this limit is reached
const getIsAboveMaxNativeZoom: boolean = $computed(() => {
  const maxNativeZoom: number = currentTileLayer.options.maxNativeZoom ?? 0;
  return currentZoom > maxNativeZoom ? true : false;
});

/**
 * Build marker popup
 */
const getMarkerPopup = (resource: any): string => {

  // Some records are missing title
  let resourceTitle = 'No Title';
  if(resource.data?.title?.text) {
    resourceTitle = resource.data?.title?.text;
  }

  let title = "<p><strong>"+utils.escHtml(resourceTitle)+"</strong></p>";
  let description = resource.data.description?.text;
  let resourceLocationsLatLon = "";
  let resourceLocation = "";
  // @ts-ignore
  let resourcePage = '<p><a href="'+process.env.ARIADNE_PUBLIC_PATH+'resource/'+ utils.escHtml(resource.id) +'">View resource</a></p>';
  let publishers = "";

  // Resource description
  if(description) {
    if (description?.length > 100) {
      description = description.slice(0, 100) + '...';
    }
  } else {
    description = "";
  }
  description = "<p>"+utils.escHtml(description)+"</p>"

  // Resource publishers
  for (let currentPublisher of resource.data.publisher) {
    publishers += utils.escHtml(currentPublisher.name) + "<br>";
  }
  publishers = "<p><strong>Publisher:</strong><br/>"+publishers+"</p>";

  // Resource location
  for (let resourceLocations of resource.data.spatial) {
    if(resourceLocations.geopoint) {
      resourceLocationsLatLon += utils.escHtml(resourceLocations.geopoint.lat + ":" + resourceLocations.geopoint.lon) + "<br>";
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
const setupHeatMap = async () => {

  //const currentHeatPoints = currentResultState.aggs?.geogrid?.grids.buckets;
  // CENTROID HEATS - Run instead of above when centroids are loaded to public portal
  const currentHeatPoints = currentResultState.aggs?.geogridCentroid?.grids.buckets;

  let max = currentHeatPoints ? Math.max.apply(null, currentHeatPoints.map((hp: any) => hp.doc_count || 0)) : 0;

  let mapPoints: any[] = [];
  let boundsPoints: any[] = [];

  currentHeatPoints?.forEach((hp: any) => {
    let decoded = Geohash.decode(hp["key"]);
    mapPoints.push([decoded.lat, decoded.lon, hp["doc_count"]]);
    boundsPoints.push([decoded.lat, decoded.lon]);
  });

  heatMap = L.heatLayer(mapPoints, {
    radius: 10,
    max: max,
    maxZoom: 9,
    gradient: {
      "0": "#8FADBB", // Light Blue
      "0.25": "#135C77", // Blue
      "0.5": "#75A99D", // Green
      "0.75": "#D5A03A", // Yellow
      "1": "#BB3921", // Red
    },
    minOpacity: 0.8,
  }).addTo(mapObj);

  // return bounds for centering arround this heatmap
  // NOTICE: As soon as we have Elastic >= 7.14 we can pick bounds from geobounds aggregation
  // Current version is 7.4 and it doesn't support geo_bounds for geoshapes
  currentMapBounds = L.latLngBounds(boundsPoints)

}


/**
 * Event for drawing shapes on map
 */
const setupDrawCreated = () => {
  mapObj.on("draw:created", (shape: any) => {

    // Remove draw layer from map if any.
    if (drawLayer) {
      mapObj.removeLayer(drawLayer);
      drawLayer = null;
    }

    // Set drawn layer
    drawLayer = shape.layer;
    mapObj.fitBounds(drawLayer.getBounds());
  });
}

/**
 * Setup moveEnd event
 * Each time the map is moved, fetch new data to re-render map layers with new boundingbox
 */
const setupOnMove = () => {
  mapObj.once('moveend', () => {
    // Fetch search result with current boundingbox and set to store.
    const currentBBox = getCurrentBoundingBox();
    if (currentBBox) {
      searchModule.setSearch({
        bbox: currentBBox,
        clear: false,
        replaceRoute: true,
        loadingStatus: LoadingStatus.Background,
      });
    }
  });
};

/**
 * Sets up map skeleton
 */
const setupMapBody = async () => {
  // setup map div/css layer. Height is a prop set in BrowseWhere.vue
  (document.getElementById('mapWrapper') as any).innerHTML = `<div id="map" style="height: calc(100vh - 12rem)"></div>`;

  mapObj = L.map("map", {
    zoomControl: false,
    scrollWheelZoom: true,
    wheelDebounceTime: 300,
    worldCopyJump: true,
    maxBounds: L.latLngBounds([-90,-180],[90,180]),
    tap: false // This needs to be here for Safari users. If not the the marker popups don't work! TODO: test for tabs and smartphones!
  });
  hasMapData = true;

  // Setup and add draw controls
  // Toolips for buttons
  L.drawLocal.draw.toolbar.buttons.polygon = "Draw a polygon and zoom in";
  L.drawLocal.draw.toolbar.buttons.rectangle = "Draw a rectangle and zoom in";

  const drawnItems = new L.FeatureGroup();
  mapObj.addLayer(drawnItems);
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
  mapObj.addControl(drawControl);

  // Add zoom controls
  mapObj.addControl(L.control.zoom({ position: "bottomright" }), {
    drawControl: true,
  });

  // Add initial (OSM) tile layer to map
  currentTileLayer.addTo(mapObj);

  // Add add tile layers to layer picker
  L.control.layers(allTileLayers, undefined, { position: 'bottomright' }).addTo(mapObj);

  // update current tile layer variable on change
  mapObj.on('baselayerchange', (newLayer: L.LayersControlEvent) => {
    const newLayerName: string = newLayer.name;
    const layers: any = allTileLayers;

    currentTileLayer = layers[newLayerName];
  });
}


/**
 * Center map with correpsonding existings data
 */
const centerMap = () => {
  if (currentMapBounds && Object.keys(currentMapBounds).length) {
    mapObj.fitBounds(currentMapBounds);
  } else if (params?.bbox) {
    const bounds = params.bbox.split(',');
    mapObj.fitBounds([
      [bounds[0],bounds[1]],
      [bounds[2],bounds[3]]
    ], { padding: L.point(10, 10) });
  } else {
    mapObj.fitWorld();
  }
}

/**
 * Get current boundingbox set by map move events
 * Returning coordinates as: upperLeft.lat, upperLeft.lng, lowerRight.lat, lowerRight.lng
 * NOTICE: L.latLngBounds does NOT return the coordinates as upperLeft, lowerRight!!
 */
const getCurrentBoundingBox = () => {
  const northWest = mapObj.getBounds().getNorthWest();
  const southEast = mapObj.getBounds().getSouthEast();

  // This might seem strange but wrap() doesn't work as intended.
  // Wrapping lat/lng still gives strange results.

  if (northWest.lat > 90) {
    northWest.lat = 90;
  }
  if (northWest.lng < -180) {
    northWest.lng = -180;
  }
  if (southEast.lat < -90) {
    southEast.lat = -90;
  }
  if (southEast.lng > 180) {
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

const getMarkerIconType = (type: any): L.Icon => {
  return L.icon({
    iconUrl: type,
    iconSize: [25, 41],
    iconAnchor: [12, 40],
    shadowUrl: markerTypes.shadow.marker,
    shadowSize: [41, 41],
    shadowAnchor: [12, 40],
  });
}

/**
 * Clear heat and marker layers
 */
const resetMap = () => {
  // Reset draw layer
  if (drawLayer) {
    mapObj.removeLayer(drawLayer);
    drawLayer = null;
  }

  // Reset heat layer
  if (heatMap) {
    mapObj.removeLayer(heatMap);
    heatMap = null;
  }

  // Reset marker layer
  if (clusterMarkers) {
    mapObj.removeLayer(clusterMarkers);
    clusterMarkers = null;
  }
}

const unwatch = watch($$(currentResultState), setMap);
onBeforeRouteLeave(unwatch);
</script>
