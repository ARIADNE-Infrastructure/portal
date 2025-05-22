<template>
  <div class="ariadne-map ariadne-map--resource">
    <div v-show="hasMapData" class="xxx-mb-lg" :class="{ 'leaflet-wrapper--hide-similar': !showNearby }">
      <div id="map" :style="{ height: getMapSize() }"></div>

      <div class="bg-lightGray-60">
        <div class="max-w-screen-xl mx-auto block md:flex md:justify-between items-center text-center text-midGray text-sm">

          <div class="py-md px-base flex items-center w-full">

            <i v-if="isPolygonSpatial"
              class="fa fa-draw-polygon text-red text-2xl mr-sm"
            />
            <img v-else
              :src="markerTypes.point.current"
              width="15"
              class="inline mr-sm"
              alt="current marker"
            >

            Current resource <div v-if="locationIsApproximate">&nbsp;( Resource location is approximate )</div>

            <template v-if="hasNearbyResources">
              <img
                :src="markerTypes.point.marker"
                width="15"
                class="inline ml-lg mr-sm"
                alt="similar marker"
              >
              Nearby resource
              <b-link
                class="ml-sm"
                :clickFn="() => toggleNearby()"
              >
                <b v-if="showNearby">(hide)</b><b v-else>(show)</b>
              </b-link>
            </template>

            <div class="ml-auto relative">
              <i class="fas fa-expand text-lg transition-color duration-300 hover:text-green px-sm"
                @mouseover="activeMapSizePopup = true"
                @mouseleave="activeMapSizePopup = false"
              />
              <div class="absolute bottom-0 right-0 bg-black-80 px-base py-base text-mmd text-white text-left whitespace-no-wrap transition-all duration-300"
                :class="activeMapSizePopup ? 'z-30 opacity-100' : 'z-neg10 opacity-0'"
                @mouseover="activeMapSizePopup = true"
                @mouseleave="activeMapSizePopup = false">
                <b class="text-base">Map size:</b>
                <ul class="pt-sm pl-lg list-disc">
                  <li class="mb-xs">
                    <a href="#" class="hover:underline" :class="{ underline: mapSize === 'small' }" @click.prevent="setMapSize('small')">
                      Small
                    </a>
                  </li>
                  <li class="mb-xs">
                    <a href="#" class="hover:underline" :class="{ underline: mapSize === 'medium' }" @click.prevent="setMapSize('medium')">
                      Medium
                    </a>
                  </li>
                  <li>
                    <a href="#" class="hover:underline" :class="{ underline: mapSize === 'large' }" @click.prevent="setMapSize('large')">
                      Full screen
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div v-if="!hasMapData" class="pb-md"></div>
  </div>
</template>

<script setup lang="ts">
import { nextTick, onMounted } from 'vue';
import { $ref, $computed } from 'vue/macros';
import { resourceModule, searchModule, generalModule } from "@/store/modules";
import * as L from 'leaflet';
import 'leaflet.heat';
import BLink from '../Base/Link.vue';
import utils from '@/utils/utils';

// WKT reader/helper for map
import 'wicket/wicket-leaflet';
import Wkt from 'wicket';

let mapObj: any = null;

let hasMapData: boolean = $ref(true);
let showNearby: boolean = $ref(true); // Show similar as default
let isPolygonSpatial: boolean = $ref(false);
let locationIsApproximate: boolean = $ref(false);
let activeMapSizePopup: boolean = $ref(false);
let mapSize: string = $ref('small');

const nearbyMarkersGroup = L.featureGroup();
const resourceMarkersGroup = L.featureGroup();
const mapZoomPadding = { padding: [20,20] };
const markerTypes = utils.getMarkerTypes(generalModule);

const resource = $computed(() => resourceModule.getResource);
const hasNearbyResources: boolean = $computed(() => !!resource?.nearby?.length);
const params = $computed(() => searchModule.getParams);

onMounted(() => {
  setupMap();
  nextTick(setMap);
});

const setMapSize = (size: string) => {
  mapSize = size;
  nextTick(() => mapObj.invalidateSize());
}

const getMapSize = (): string => {
  if (mapSize === 'small') {
    return '300px';
  }
  if (mapSize === 'medium') {
    return 'max(50vh, 400px)';
  }
  return 'calc(100vh - ' + (utils.objectIsNotEmpty(params) ? 10 : 7.5) + 'rem)';
}

/**
 * Toggle nearby resources
 */
const toggleNearby = (): void => {
  showNearby = !showNearby;
  if(showNearby) {
    let allLayers = resourceMarkersGroup.getLayers().concat(nearbyMarkersGroup.getLayers());
    mapObj.addLayer(nearbyMarkersGroup);
    mapObj.fitBounds(L.featureGroup(allLayers).getBounds(),mapZoomPadding);
  } else {
    mapObj.removeLayer(nearbyMarkersGroup);
    mapObj.fitBounds(resourceMarkersGroup.getBounds(),mapZoomPadding);
  }
}

/**
 * Map body
 */
const setupMap = () => {
  mapObj = L.map('map', {
    zoomControl: false,
    scrollWheelZoom: false
  });

  const allTileLayers = utils.getTileLayers(L, false, false);

  allTileLayers.OSM.addTo(mapObj);

  L.control.layers(allTileLayers, undefined, { position: 'bottomright' }).addTo(mapObj);

  mapObj.addControl(L.control.zoom({ position: 'bottomright' }));
}

/**
 * Map content, markers and geoshapes
 */
const setMap = async () => {
  let marker: any = null;
  isPolygonSpatial = false;

  // Pane allows for settings z-index. All other documented approaches fail!
  mapObj.createPane('resourceMarkersPane');
  mapObj.getPane('resourceMarkersPane').style.zIndex = 20;

  // Get locations from resource and create markers
  resource.spatial?.forEach((spatial: any, i: number) => {
    if (spatial.spatialPrecision || spatial.coordinatePrecision ) {
      // If either of these props are set, means that resource location is apporoximate!
      locationIsApproximate = true;
    }

    // Handle geopoint data
    if (spatial?.geopoint) {
      // Create and set marker to current map
      marker = getMarker(resource.id, spatial, markerTypes.point.current, resourceModule.getMainTitle(resource) );
      marker.addTo(resourceMarkersGroup);
    }

    // Handle polygon data and/or boundingbox
    if (spatial?.polygon || spatial?.boundingbox) {
      // incoming data can either be polygon or boundingbox. Handles the same way.
      let shape = spatial?.polygon || spatial?.boundingbox;

      // Create a new Wicket instance
      // DOCS: http://arthur-e.github.io/Wicket/doc/out/Wkt.Wkt.html
      const wkt = new Wkt.Wkt();
      wkt.read(shape);
      // Add to custom pane to be able to set z-index for geoshapes
      // Interactive: false => because we must be able to show tooltips for nearby markers under the geoshape
      let feature = wkt.toObject({color: 'red', pane: 'resourceMarkersPane', interactive: false});

      // Add polygon for fitbounds
      //feature.bringToFront();
      feature.addTo(resourceMarkersGroup);
      isPolygonSpatial = true;
    }
  });

  // No locations found means no map needed, return.
  if (!resourceMarkersGroup?.getLayers().length) {
    // Set map null to hide!
    mapObj = null;
    hasMapData = false;
    return;
  }

  // Get current resources nearby locations and create markers
  if (resource.nearby) {
    resource.nearby?.forEach((nearbyResource: any) => {
      nearbyResource.spatial?.forEach((spatial: any) => {
        let marker = getMarker(nearbyResource.id, spatial, null, resourceModule.getMainTitle(nearbyResource));
        if (marker) {
          marker.addTo(nearbyMarkersGroup);
        }
      });
    });
  }

  // Set nearby layers Z-index way back
  bringLayersToBack(nearbyMarkersGroup);
  nearbyMarkersGroup.addTo(mapObj);
  resourceMarkersGroup.addTo(mapObj);

  // fitBounds for polygons needs delay!!
  await utils.delay(50);
  let allLayers = resourceMarkersGroup.getLayers().concat(nearbyMarkersGroup.getLayers());
  const group = L.featureGroup(allLayers);
  mapObj.fitBounds(group.getBounds(),mapZoomPadding);

  nextTick(() => {
    mapObj.invalidateSize();
  });
}

/**
 * Create markers to render on map
 */
const getMarker = (resourceId: any, spatial: any, markerType: any, markerLabel: string): L.Marker | null => {
  let latLng: L.LatLng | null = null;

  if (spatial.geopoint) {
    // spatial is geopoint
    latLng = L.latLng(spatial.geopoint.lat, spatial.geopoint.lon );
    markerType == null ? markerType = markerTypes.point.marker : markerType = markerType;
  } else {
    // spatial is geoshape
    let geoShape = spatial?.polygon || spatial?.boundingbox;
    if (geoShape) {
      // get center of geoshape
      const wkt = new Wkt.Wkt();
      wkt.read(geoShape);
      const feature = wkt.toObject({ color: 'red' });
      if (feature.getBounds) {
        latLng = L.latLng(feature.getBounds().getCenter().lat, feature.getBounds().getCenter().lng );
      } else {
        latLng = L.latLng({ lat: feature._latlng.lat, lng: feature._latlng.lat });
      }
      markerType == null ? markerType = markerTypes.point.shape : markerType = markerType;
    }
  }

  if (latLng) {
    let marker = L.marker(latLng, getMarkerStyle(markerType));
    if (markerLabel) {
      marker.bindTooltip(markerLabel, { direction: 'top', offset: [0, -35] });
    }
    // Link all makrkers that are not current resource
    if (markerType != markerTypes.point.current) {
      marker.on('click', () => {
        resourceModule.navigateToResource(resourceId);
      });
    }
    return marker;
  }
  return null;
}

/**
 * Bring all layers in group to back
 */
const bringLayersToBack = (layerGroup: any) => {
  layerGroup.eachLayer((layer: any) => {
    layer.setZIndexOffset(-2000);
  });
}

/**
 * Resource marker style
 */
const getMarkerStyle = (markerType: any) => {
  return {
    icon: L.icon({
      iconUrl: markerType,
      iconSize: [25, 41],
      iconAnchor: [12, 40],
      shadowUrl: markerTypes.shadow.marker,
      shadowSize: [41, 41],
      shadowAnchor: [12, 40]
    }),
    riseOnHover: true
  }
}
</script>
