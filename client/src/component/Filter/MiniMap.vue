<template>
  <div class="relative border-base border-gray ariadne-map">
    <!-- map frame controls -->
    <transition
      v-on:before-enter="transitionLeave" v-on:enter="transitionEnter"
      v-on:before-leave="transitionEnter" v-on:leave="transitionLeave"
    >
      <div
        v-if="mapHasHits && !noTopBar"
        class="bg-lightGray ease-out duration-200 overflow-hidden"
      >
        <div class="p-sm items-center flex justify-between">
          <span class="text-md">{{ title }}</span>
          <div class="flex overflow-hidden">
            <button v-if="canSearch"
              class="bg-blue-50 px-md py-sm text-center text-sm text-white hover:bg-blue transition-all duration-300 mr-md"
              :class="showAreaSearch ? 'cursor-pointer opacity-100' : 'cursor-default opacity-0'"
              @click.prevent="searchCurrentArea()"
            >
              <i class="fas fa-search mr-xs" />
              Search area
            </button>
            <button
              class="bg-yellow px-md py-sm text-center text-sm text-white cursor-pointer hover:bg-green transition-color duration-300"
              @click.prevent="showResultInMapView()"
            >
              <i class="fas fa-search mr-xs" />
              Advanced Search
            </button>
          </div>
        </div>
      </div>
    </transition>

    <div
      class="relative"
      style="height: 250px;"
    >
      <div v-if="!mapHasHits"
        class="absolute top-0 left-0 h-full w-full bg-lightGray z-19 text-darkGray text-xl flex items-center justify-center"
      >
        {{ isLoading ? 'Loading..' : 'No locations found' }}
      </div>
      <div v-else
        class="absolute top-0 left-0 w-full h-full transition-opacity duration-300 cursor-default flex justify-center items-center" :class="isOnlyLoadingMap ? 'z-20 opacity-100' : 'z-neg10 opacity-0'">
        <i class="fas fa-spinner fa-spin mr-sm text-2x text-darkGray"></i>
      </div>

      <!-- map -->
      <div
        id="mapWrapper"
        style="height: 250px;"
      />
    </div>

    <!-- map info -->
    <transition
      v-on:before-enter="transitionLeave" v-on:enter="transitionEnter"
      v-on:before-leave="transitionEnter" v-on:leave="transitionLeave"
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
                  :src="markerTypes.point.marker"
                  width="15"
                  class="inline mr-sm"
                  alt="similar marker"
                >
                Geo point
              </li>
              <li class="ml-lg flex items-center h-2x">
                <img
                  :src="markerTypes.point.shape"
                  width="15"
                  class="inline mr-sm"
                  alt="current marker"
                >
                Geo shape
              </li>
              <li class="ml-lg flex items-center h-2x">
                <img
                  :src="markerTypes.combo.marker"
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

<script setup lang="ts">
import { watch, onMounted, onUnmounted } from 'vue';
import { $ref, $computed } from 'vue/macros';
import { useRoute, onBeforeRouteLeave } from 'vue-router'
import { searchModule, generalModule } from "@/store/modules";
import router from '@/router';
import utils from '@/utils/utils';

import * as L from "leaflet";
import Geohash from "latlon-geohash";
import "leaflet.heat";
import "leaflet.markercluster";
import "leaflet-draw";
import 'wicket/wicket-leaflet';
import Wkt from 'wicket';

defineProps<{
  title?: string,
  height?: string,
  noTopBar?: boolean,
  canSearch?: boolean,
}>();

const route = useRoute();

const markerThreshold: number = 500; // Render cluster markers when this limit is reached
let clusterMarkers: L.MarkerClusterGroup | null = null;
let heatMap: L.HeatLayer | null = null;
let drawLayer: any = null;
let mapObj: any = null;
let isUnmounted: boolean = false;
let canAreaSearch: boolean = false;
const markerTypes = utils.getMarkerTypes(generalModule);

let showMarkerInfo = $ref(false);
let mapHasHits = $ref(false);
let showAreaSearch = $ref(false);

const globalParams = $computed(() => searchModule.getParams);
const miniMapSearchResult = $computed(() => searchModule.getMiniMapSearchResult);
const isLoading = $computed(() => generalModule.getIsLoading || searchModule.getIsAggsLoading || searchModule.getIsMapLoading);
const isOnlyLoadingMap = $computed(() => searchModule.getIsMapLoading && !generalModule.getIsLoading && !searchModule.getIsAggsLoading);

const transitionEnter = (el: HTMLElement) => {
  el.style.height = `${ el.scrollHeight}px`;
}
const transitionLeave = (el: HTMLElement) => {
  el.style.height = '0px';
}

const setAreaSearch = (e: any) => {
  showAreaSearch = canAreaSearch;
  canAreaSearch = true;
}
const setShowAreaSearch = () => {
  showAreaSearch = true;
  canAreaSearch = true;
}
const resetAreaSearch = () => {
  canAreaSearch = false;
  showAreaSearch = false;
}

/**
 * Updates minimap accordning to route params
 * Fired by  @Watch('$route', { immediate: true })
 */
const setMiniMapFromState = async (noCenter: boolean = false) => {
  if (isUnmounted) {
    return;
  }
  resetAreaSearch();
  if (drawLayer) {
    mapObj.removeLayer(drawLayer);
    drawLayer = null;
  }
  if (heatMap) {
    mapObj.removeLayer(heatMap);
    heatMap = null;
  }
  if (clusterMarkers) {
    mapObj.removeLayer(clusterMarkers);
    clusterMarkers = null;
  }
  let resourceHitsCount = miniMapSearchResult.total?.value;
  if (resourceHitsCount <= markerThreshold) {
    setClusterMarkers(miniMapSearchResult?.hits, noCenter);
    showMarkerInfo = true;
  } else {
    setHeatMap(miniMapSearchResult.aggregations?.geogridCentroid?.grids.buckets, noCenter);
    showMarkerInfo = false;
  }
}

// sets cluster markers
const setClusterMarkers = (markerResources: any, noCenter: boolean) => {
  clusterMarkers = new L.MarkerClusterGroup();
  let points: any = [];

  markerResources.forEach((resource: any) => {
    if (!resource.data?.spatial) {
      return;
    }

    resource.data.spatial.forEach((spatial: any) => {
      let markerType: any = markerTypes.point;

      if (spatial.spatialPrecision || spatial.coordinatePrecision) {
        markerType = markerTypes.approx;
      }

      if (spatial?.geopoint) {

        // points needed for fitbBounds (center map)
        points.push([spatial.geopoint.lat, spatial.geopoint.lon]);

        let marker = L.marker(
          new L.LatLng(spatial.geopoint.lat, spatial.geopoint.lon),
          { icon: getMarkerIconType(markerType.marker) }
        );

        marker.bindTooltip(resource.data.title.text, { keepInView: true, direction: 'top', noWrap: false, offset: [0, -35] } as L.TooltipOptions);

        marker.on('click', () => {
          window.location.href = process.env.ARIADNE_PUBLIC_PATH+'resource/'+ resource.id;
        });

        clusterMarkers!.addLayer(marker);

      } else if (spatial?.polygon || spatial?.boundingbox) {
        // Create a new Wicket instance
        // DOCS: http://arthur-e.github.io/Wicket/doc/out/Wkt.Wkt.html
        const wkt = new Wkt.Wkt();
        // Read in any kind of WKT string
        let shape = spatial?.polygon || spatial?.boundingbox;
        wkt.read(shape);
        const feature = wkt.toObject({color: 'red'});

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

        polygonMarker.bindTooltip(resource.data.title.text, { keepInView: true, direction: 'top', noWrap: true, offset: [0, -35] } as L.TooltipOptions);

        polygonMarker.on('click', () => {
          window.location.href = process.env.ARIADNE_PUBLIC_PATH+'resource/'+ resource.id;
        });

        // points needed for fitbBounds (center map)
        points.push([polygonCenter.lat,polygonCenter.lng]);

        clusterMarkers!.addLayer(polygonMarker);
      }
    });
  });

  // add markers to map layer
  mapObj.addLayer(clusterMarkers);

  // center mini map arround points
  if (!noCenter) {
    centerMiniMap(points);
    mapHasHits = !!points.length;
  }
  points = null;
}

// sets heatmap with geogrids from agg
const setHeatMap = (heatPoints: any, noCenter: boolean) => {
  if (!heatPoints) {
    return;
  }

  let max = Math.max.apply(null, heatPoints?.map((hp: any) => hp.doc_count || 0));
  let mapPoints: any[] = [];
  let points: any = [];

  heatPoints.forEach((hp: any) => {
    let decoded = Geohash.decode(hp["key"]);
    mapPoints.push([decoded.lat, decoded.lon, hp["doc_count"]]);
    points.push([decoded.lat, decoded.lon]);
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

  // center map arround heatpoints
  if (!noCenter) {
    centerMiniMap(points);
    mapHasHits = !!points.length;
  }
  mapPoints = [];
  points = [];
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
 * Centers map arround given lat/lon points'
 */
const centerMiniMap = (points: any[]) => {
  // center map arround heatpoints
  if (points.length) {
    mapObj.fitBounds(L.latLngBounds(points), { padding: L.point(15, 15)});
  }
  points = [];
}

const searchCurrentArea = async () => {
  if (!showAreaSearch) {
    return;
  }
  resetAreaSearch();
  const ghp = (mapObj.getZoom() || 10) + 2;
  const northWest = mapObj.getBounds().getNorthWest();
  const southEast = mapObj.getBounds().getSouthEast();
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
  const bbox = [northWest.lat, northWest.lng, southEast.lat, southEast.lng].toString();
  const query = { ...globalParams, bbox, ghp };
  delete query.page;
  searchModule.updateMiniMapNoCenter(true);
  router.push({ path: '/search', query });
}

/**
 * Redirect user to map view with current search criterias
 */
const showResultInMapView = () => {
  router.push({ path: '/browse/where', query: router.currentRoute.value.query });
}

// sets up map body - prepares mapObj with an L.map()
onMounted(() => {
  mapObj = L.map('mapWrapper', {
    zoomControl: true,
    worldCopyJump: true,
    scrollWheelZoom: false,
    dragging: true,
    boxZoom: false,
    doubleClickZoom: true,
    tap: false
  });

  mapObj.on('resize', resetAreaSearch);
  mapObj.on('moveend', setAreaSearch);
  mapObj.on('mousedown', setShowAreaSearch);
  mapObj.zoomControl?._zoomInButton?.addEventListener('mousedown', setShowAreaSearch)
  mapObj.zoomControl?._zoomOutButton?.addEventListener('mousedown', setShowAreaSearch)
  mapObj.zoomControl?._zoomInButton?.addEventListener('touchstart', setShowAreaSearch)
  mapObj.zoomControl?._zoomOutButton?.addEventListener('touchstart', setShowAreaSearch)

  mapObj.setMaxBounds(L.latLngBounds([-90,-180], [90,180]));

  const allTileLayers = utils.getTileLayers(L, true, false);
  allTileLayers.OSM.addTo(mapObj);
  L.control.layers(allTileLayers, undefined, { position: 'topleft' }).addTo(mapObj);
});

/**
 * When route changes, update minimap.
 */
watch(route, async () => {
  if (isUnmounted) {
    return;
  }
  resetAreaSearch();
  await searchModule.setMiniMapSearch(globalParams);
  setMiniMapFromState(searchModule.getMiniMapNoCenter);
  searchModule.updateMiniMapNoCenter(false);
}, { immediate: true, deep: true });

// clear some data when minimap is unmounted
const clearMinimap = () => {
  mapObj?.off('resize', resetAreaSearch);
  mapObj?.off('moveend', setAreaSearch);
  mapObj?.off('mousedown', setShowAreaSearch);
  mapObj?.zoomControl?._zoomInButton?.removeEventListener('mousedown', setShowAreaSearch)
  mapObj?.zoomControl?._zoomOutButton?.removeEventListener('mousedown', setShowAreaSearch)
  mapObj?.zoomControl?._zoomInButton?.removeEventListener('touchstart', setShowAreaSearch)
  mapObj?.zoomControl?._zoomOutButton?.removeEventListener('touchstart', setShowAreaSearch)
  mapObj?.off();
  mapObj = null;
  isUnmounted = true;
}

onUnmounted(clearMinimap);
onBeforeRouteLeave(clearMinimap);
</script>
