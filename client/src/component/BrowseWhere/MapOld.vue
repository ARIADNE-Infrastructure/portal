<template>
  <div v-show="map" class="mb-lg">
    <!-- map -->
    <div ref="mapWrapper"></div>

    <!-- map info -->
    <div v-if="showInfo" class="bg-lightGray">
      <div class="max-w-screen-xl mx-auto">
        <div class="block md:flex md:justify-between items-center text-center text-midGray text-md">
          <div class="px-base pt-lg pb-xs md:py-md md:px-base">
            Map shows current &amp; geographical similar resources.
          </div>

          <div class="py-md px-base">
            <img :src="`${ assets }/leaflet/marker-icon-red.png`" width="15" class="inline mr-xs" alt="current marker">
            Current resource

            <img :src="`${ assets }/leaflet/marker-icon-blue.png`" width="15" class="inline ml-lg mr-xs" alt="similar marker">
            Similar resource
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Prop, Watch } from 'vue-property-decorator';
import { search, general, resource } from "@/store/modules";
import axios from 'axios';
import * as L from 'leaflet';
import Geohash from 'latlon-geohash';
import 'leaflet.heat';
import 'leaflet/dist/leaflet.css';

import utils from '@/utils/utils';
import HelpTooltip from '@/component/Help/Tooltip.vue';

@Component({
  components: {
    HelpTooltip
  }
})
export default class BrowseWhereMapOld extends Vue {
  @Prop() height!: string;
  @Prop() isMultiple?: boolean;
  @Prop() noZoom?: boolean;
  @Prop() showInfo?: boolean;
  map: any = null;
  heatMap: any = null;
  markers: Array<any> = [];
  localData: any = {};
  isLocalLoading: boolean = false;
  canMoveChange: boolean = false;
  utils = utils;

  mounted () {
    if (!this.isMultiple || this.result) {
      this.setMap();
    }
  }
  
  destroyed () {
    this.resetAll();
  }

  get isLoading(): boolean {
    return general.getLoading;
  }

  get result(): any {
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

  navigate (path?: string) {
    if (path) {
      this.$router.push(utils.paramsToString(path, this.localData.params || this.params));

    } else {
      search.setSearch(this.localData.params || this.params);
    }
  }

  @Watch('isLoading')
  loadingChange () {
    this.localData = {};
    this.setMap();
  }

  resetAll () {
    this.resetMap();
    if (this.map) {
      this.map.remove();
      this.map = null;
    }
  }

  resetMap () {
    this.canMoveChange = false;

    if (this.heatMap) {
			this.map.removeLayer(this.heatMap);
      this.heatMap = null;
    }
    if (this.markers.length) {
      this.markers.forEach((marker: any) => {
        marker.closeTooltip();
        marker.unbindTooltip();
        marker.off();
        this.map.removeLayer(marker);
      });
      this.markers = [];
    }
  }

  async setMap () {
 
    if (this.isLoading) {
      return;
    }

    let spatials = [],
        heatPoints = [],
        result = this.result;

    if (this.localData.result) {
      result = this.localData.result;
    }

    if (this.isMultiple) {
      if (result?.total?.value >= 100) {
        if (!this.map || this.map.getZoom() < 17) {
          heatPoints = result?.aggs?.geogrid?.buckets;
        }
      }
      result?.hits?.forEach((hit: any) => {
        hit.data?.spatial?.forEach((spat: any) => {
          if (spat?.location) {
            spatials.push(spat);
          }
        });
      });
    } else if (this.resource?.spatial) {
      this.resource.spatial?.forEach((spat: any) => {
        if (spat?.location) {
          spat.priority = true;
          spatials.push(spat);
        }
      });
      this.resource.nearby?.forEach((spat: any) => {
        if (spat?.location) {
          spatials.push(spat);
        }
      });
    }

    if (!spatials.length && !heatPoints.length) {
      if (!this.localData.result) {
        this.resetAll();
      }
      return;
    }

    if (!this.map) {
      let mapRef: any = this.$refs.mapWrapper;
      mapRef.innerHTML = `<div id="map" style="height:${ this.height };"></div>`;
    }

    await utils.delay(10);

    if (!this.map) {
      this.map = L.map('map', {
		    zoomControl: false,
        scrollWheelZoom: !this.noZoom
      });
	    this.map.addControl(L.control.zoom({ position: 'bottomright' }));
      L.tileLayer('https://{s}.tile.osm.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
      }).addTo(this.map);

      if (this.isMultiple) {
        this.setMove();
      }
    }

    this.resetMap();

    await utils.delay(10);

    if (heatPoints.length) {
      this.setHeatMap(heatPoints);

    } else {
      this.setMarkers(spatials);
    }

    await utils.delay(this.localData.result ? 100 : 300);
    this.canMoveChange = true;
  }

  setHeatMap (heatPoints: any) {
    let max = Math.max.apply(null, heatPoints.map((hp: any) => hp.doc_count || 0)),
        mapPoints = [],
        points = [];

    heatPoints.forEach((hp: any) => {
      let decoded = Geohash.decode(hp['key']);
      mapPoints.push([decoded.lat, decoded.lon, hp['doc_count']]);
      points.push([decoded.lat, decoded.lon])
    });

    this.heatMap = L.heatLayer(mapPoints, {
      radius: 10,
      max: max,
      gradient: this.getGradient(0.7),
      minOpacity: 0.3
    }).addTo(this.map);

    if (!this.localData.result) {
      this.map.fitBounds(L.latLngBounds(points), {
        padding: L.point(25, 25)
      });
      if (!this.title) {
        this.map.zoomOut(points.length < 35 ? 2 : 1, {
          animate: false
        });
      }
    }
  }

  getGradient (opacity: number) {
    let gradient = {};

		for (let i = 1; i <= 10; i++) {
      let key = Math.round((opacity * i / 10) * 100) / 100,
          val = Math.round((1.0 - (i / 10)) * 240);
      gradient[key] = `hsl(${ val }, 90%, 50%)`;
    }
    return gradient;
  }

  setMarkers (spatials: any) {
    spatials.forEach((spatial: any) => {
      let marker = this.makeMarker(spatial);
      marker.addTo(this.map);
      this.markers.push(marker);
    });

    if (!this.localData.result) {
      this.map.fitBounds(L.featureGroup(this.markers).getBounds(), {
        animate: false,
        padding: L.point(25, 25)
      });
      this.map.panTo(L.featureGroup(this.markers).getBounds().getCenter(), {
        animate: false
      });
      if (!this.isMultiple) {
        this.map.zoomOut(1, { animate: false });
      }
    }
  }

  makeMarker = function(spatial) {
    let marker = L.marker(spatial.location, {
      icon: L.icon({
        iconUrl: `${ this.assets }/leaflet/marker-icon-${ spatial.priority ? 'red' : 'blue' }.png`,
        iconSize: [25, 41],
        iconAnchor: [12, 40],
        shadowUrl: `${ this.assets }/leaflet/marker-shadow.png`,
        shadowSize: [41, 41],
        shadowAnchor: [12, 40]
      }),
      zIndexOffset: spatial.priority ? 1000 : 0,
      riseOnHover: true
    });
    marker.bindTooltip(spatial.placeName || spatial.location.lat + ', ' + spatial.location.lon);

    marker.on('click', (ev: any) => {
      let val = spatial.placeName ||
        `spatial.location.lat:"${ spatial.location.lat }" AND spatial.location.lon:"${ spatial.location.lon }"`;

      search.setSearch({
        q: val,
        page: 0,
        fields: 'location',
        clear: true
      });
    });

    return marker;
  }

  setMove () {
    let timeout: any = false,
        stack: Array<any> = [];

    this.map.on('moveend', (ev: any) => {
      let stop = this.isLoading || !this.canMoveChange;

      clearTimeout(timeout);
      timeout = setTimeout(async () => {
        if (stop) {
          return;
        }

        const i = stack.length;
        stack.push(null);

        this.isLocalLoading = true;
        this.$emit('mapChange', true);

        stack[i] = await this.localSearch();

        if (stack.length && i >= stack.length - 1) {
          this.isLocalLoading = false;

          if (stack[stack.length - 1]) {
            this.localData.result = stack[stack.length - 1];
            this.setMap();
          }
          stack = [];
          this.$emit('mapChange', this.localData);
        }
      }, 1000);
    });
  }

  async localSearch () {
    if (!this.map) {
      return;
    }

    let zoomLevel = this.map.getZoom(),
        ghpZooms = [3, 3, 3, 3, 4, 4, 5, 5, 6, 6, 6, 7, 7, 8, 8, 8, 9, 9, 9];

    if (zoomLevel > 18) {
      zoomLevel = 18;
    } else if (zoomLevel < 0) {
      zoomLevel = 0;
    }

    let bounds = new L.latLngBounds(
      this.map.getBounds().getSouthWest().wrap(),
      this.map.getBounds().getNorthEast().wrap()
    );

    this.localData.error = false;
    this.localData.params = utils.getCopy(this.params);
    this.localData.params.ghp = ghpZooms[zoomLevel];
    this.localData.params.bbox = bounds.toBBoxString();

    try {
      // @ts-ignore
      const url = process.env.apiUrl + '/search';
      const res = await axios.get(utils.paramsToString(url, this.localData.params) + '&mapq=true');

      if (utils.objectIsNotEmpty(res?.data)) {
        return {
          total: res.data.total,
          hits: res.data.hits,
          aggs: res.data.aggregations,
        }
      } else if (this.localData) {
        this.localData.error = true;
      }
    } catch (ex) {
      if (this.localData) {
        this.localData.error = true;
      }
    }
    return null;
  }
}
</script>