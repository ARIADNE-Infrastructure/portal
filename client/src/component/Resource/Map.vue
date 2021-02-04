<template>
  <div>
    <div v-show="map" class="xxx-mb-lg" :class="{ 'show-similar': showSimilar }">
      <div ref="mapWrapper"></div>
      <div class="bg-lightGray-60">
        <div class="max-w-screen-xl mx-auto block md:flex md:justify-between items-center text-center text-midGray text-sm">
          <div class="px-base pt-lg pb-xs md:py-md md:px-base">
            Map shows location of current resource. Geographical similar are hidden by default.
          </div>

          <div class="py-md px-base">
            <img :src="`${ assets }/leaflet/marker-icon-red.png`" width="15" class="inline mr-xs" alt="current marker">
            Current resource

            <img :src="`${ assets }/leaflet/marker-icon-blue.png`" width="15" class="inline ml-lg mr-xs" alt="similar marker">
            Similar resource
            <b-link :clickFn="() => showSimilar = !showSimilar">
              <b v-if="showSimilar">(hide)</b>
              <b v-else>(show)</b>
            </b-link>
          </div>
        </div>
      </div>
    </div>
    <div v-if="!map" class="pb-md"></div>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Prop, Watch } from 'vue-property-decorator';
import { general, resource, search } from "@/store/modules";
import * as L from 'leaflet';
import Geohash from 'latlon-geohash';
import 'leaflet.heat';
import 'leaflet/dist/leaflet.css';
import BLink from '../Base/Link.vue';

@Component({
  components: {
    BLink,
  }
})
export default class ResourceMap extends Vue {
  map: any = null;
  showSimilar: boolean = false;

  mounted () {
    this.setMap();
  }

  get resource () {
    return resource.getResource;
  }

  get assets () {
    return general.getAssetsDir;
  }

  setMap (): void {
    let map: any = null;
    let mapRef: any = this.$refs.mapWrapper;
    mapRef.innerHTML = `<div id="map" style="height:300px"></div>`;

    map = L.map('map', {
      zoomControl: false,
      scrollWheelZoom: false
    });

    L.tileLayer('https://{s}.tile.osm.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Set zoom buttons
    map.addControl(L.control.zoom({ position: 'bottomright' }));

    // Array with locations to center map arround
    let locations: any[] = [];

    // Get locations from resource and create markers
    this.resource.spatial.forEach((spatial: any) => {
      if (spatial?.location) {
        locations.push([spatial.location.lat, spatial.location.lon]);
        // Create and set marker to current map
        this.createMarker(spatial, 'red').addTo(map);
      }
    });

    // Get current resources nearby locations and create red markers
    if (this.resource?.nearby) {
      this.resource.nearby.forEach((nearBy: any) => {
        if (nearBy?.location) {
          locations.push([nearBy.location.lat, nearBy.location.lon]);
          // Create and set marker to current map
          this.createMarker(nearBy, 'blue').addTo(map);
        }
      });
    }

    if (!locations.length) {
      return;
    }

    // Center map arround each spatial location in current resource
    map.fitBounds(locations);

    // Set default zoom
    map.setZoom(12);

    // Refresh map on next tick
    this.$nextTick(() => {
      map.invalidateSize();
    });

    this.map = map;
  }

  /**
   * Create markers to render on map
   */
  createMarker = function(spatial, markerColor ) {
    let marker = L.marker(spatial.location, {
      icon: L.icon({
        iconUrl: `${ this.assets }/leaflet/marker-icon-`+markerColor+`.png`,
        iconSize: [25, 41],
        iconAnchor: [12, 40],
        shadowUrl: `${ this.assets }/leaflet/marker-shadow.png`,
        shadowSize: [41, 41],
        shadowAnchor: [12, 40],
        className: markerColor !== 'red' ? 'similar' : ''
      }),
      zIndexOffset: markerColor === 'red' ? 200 : 100,
      riseOnHover: true
    });
    marker.bindTooltip(spatial.placeName || spatial.location.lat + ', ' + spatial.location.lon);

    marker.on('click', (ev: any) => {
      let val = spatial.placeName ||
        `spatial.location.lat:"${ spatial.location.lat }" AND spatial.location.lon:"${ spatial.location.lon }"`;

      search.setSearch({
        path: '/search',
        q: val,
        page: 0,
        fields: 'location',
        clear: true
      });
    });
    return marker;
  }
}
</script>

<style>
.similar{
  display: none;
}
.show-similar .similar{
  display: block;
}
</style>
