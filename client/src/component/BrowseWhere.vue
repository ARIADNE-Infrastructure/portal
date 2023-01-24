<template>
	<div>
    <browse-where-map @markerViewUpdate="inMarkerView = $event" />

    <div class="relative w-toolbar top-2xl left-0 mb-lg shadow-full z-10">
      <!-- totals -->
      <browse-where-totals :data="data" />

      <transition
        v-on:before-enter="markerViewLeave" v-on:enter="markerViewEnter"
        v-on:before-leave="markerViewEnter" v-on:leave="markerViewLeave"
      >
        <div
          v-if="inMarkerView"
          class="ease-out duration-200 overflow-hidden"
        >
          <ul class="flex justify-center text-mmd text-black border-t-base border-gray p-md bg-white">
            <li class="flex items-center h-2x">
              <img
                :src="mapUtils.markerType.point.marker"
                width="15"
                class="mr-sm"
                alt="Blue marker - Geo point"
              >
              Geo point
            </li>

            <li class="ml-lg flex items-center h-2x">
              <img
                :src="mapUtils.markerType.point.shape"
                width="15"
                class="mr-sm"
                alt="Red marker - Geo shape"
              >
              Geo shape
            </li>

            <li class="ml-lg flex items-center h-2x">
              <img
                :src="mapUtils.markerType.combo.marker"
                width="21"
                class="mr-sm"
                alt="Approximately location marker"
              >
              Approx. location
            </li>

          </ul>
        </div>
      </transition>

      <!-- display as search results -->
      <div
        class="bg-yellow p-md text-center text-mmd text-white cursor-pointer hover:bg-green transition-color duration-300"
        @click="toSearch"
      >
        <i class="fas fa-search mr-sm"></i>
        Display as search result
      </div>
    </div>

    <!-- filters -->
    <filter-toggleable :defaultView="true" :lockBodyOnActive="false">
      <filter-list :show="['search', 'yearsAndPeriods', 'aggregations']" />
    </filter-toggleable>

    <div class="fixed top-0 left-0 w-screen h-screen z-neg10 bg-blue-40"></div>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { onMounted } from 'vue';
import { $ref } from 'vue/macros';
import { searchModule, generalModule } from "@/store/modules";
import utils from '@/utils/utils';
import mapUtils from '@/utils/map';

import BrowseWhereMap from './BrowseWhere/Map.vue';
import FilterToggleable from '@/component/Filter/Toggleable.vue';
import FilterList from '@/component/Filter/List.vue';
import BrowseWhereTotals from './BrowseWhere/Totals.vue';
import router from '@/router';

const data = $ref(null);
const inMarkerView: boolean = $ref(false);

const params = $computed(() => searchModule.getParams);

onMounted(() => {
  // set an initial search based on current route (while still updating url)
  searchModule.setSearch({
    ...router.currentRoute.value.query,
    ...{
      clear: true,
      forceReload: 'true',
      mapq: 'true'
    }
  });
});

const markerViewEnter = (el: HTMLElement): void => {
  el.style.height = `${ el.scrollHeight}px`;
}

const  markerViewLeave = (el: HTMLElement): void => {
  el.style.height = '0px';
}

const toSearch = (): void => {
  searchModule.setSearch({ path: '/search', bbox: params?.bbox, forceReload: null, mapq: null });
}
</script>
