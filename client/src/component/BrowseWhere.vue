<template>
	<div>
    <browse-where-map @markerViewUpdate="inMarkerView = $event" />

    <div class="relative">
      <div class="absolute w-toolbar top-2xl right-0 text-center shadow-full z-1001">
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
          class="bg-yellow p-md text-mmd text-white cursor-pointer hover:bg-green transition-color rounded-bl-base duration-300"
          @click="toSearch"
        >
          <i class="fas fa-search mr-xs"></i>
          Display as search result
        </div>
      </div>

      <div
        class="absolute left-0 z-1001"
        style="height:calc(100vh - 12.75rem)"
      >
        <div class="relative h-full">
          <div
            class="absolute left-0 top-2xl w-toolbar bg-white rounded-br-base transition-all ease-out transform duration-300"
            :class="{'-translate-x-toolbar': !showView, 'shadow-full': showView}"
          >
            <div class="relative">
              <div class="p-base pr-none">
                <ul
                  class="bg-white rounded-r-base inline-block shadow-full absolute top-0 left-full"
                >
                  <li
                    @click="toggleView('filters')"
                    class="px-md py-sm cursor-pointer text-md"
                  >
                    Filters
                  </li>
                </ul>

                <div v-if="view === 'filters'">
                  <h2 class="text-lg mb-md">
                    Filters
                  </h2>

                  <div class="pr-lg">
                    <filter-search
                      color="blue"
                      hoverStyle="hover:bg-blue-80"
                      focusStyle="focus:border-blue"
                      class="mb-lg"
                      :breakHg="true"
                      :big="true"
                      :useCurrentSearch="true"
                      showFields="select"
                      :hasAutocomplete="true"
                      :stayOnPage="true"
                      size="sm"
                      @submit="utils.blurMobile()"
                    />
                  </div>

                  <filter-years class="mb-lg pr-lg" />

                  <filter-aggregations
                    size="sm"
                    max-height="45vh"
                  />
                </div>
              </div>

              <filter-clear
                class="rounded-bl-0 rounded-t-0"
                :ignoreParams="['page', 'sort', 'order', 'mapq', 'bbox', 'size', 'ghp', 'loadingStatus', 'forceReload']"
              />
            </div>
          </div>

        </div>
      </div>
    </div>

    <div class="fixed top-0 left-0 w-screen h-screen z-neg bg-blue-40"></div>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import { searchModule, generalModule } from "@/store/modules";
import utils from '@/utils/utils';
import mapUtils from '@/utils/map';

import BrowseWhereMap from './BrowseWhere/Map.vue';
import FilterSearch from '@/component/Filter/Search.vue';
import FilterYears from '@/component/Filter/Years.vue';
import FilterAggregations from '@/component/Filter/Aggregations.vue';
import FilterClear from '@/component/Filter/Clear.vue';
import BrowseWhereTotals from './BrowseWhere/Totals.vue';
import router from '@/router';

@Component({
  components: {
    BrowseWhereMap,
    FilterSearch,
    FilterYears,
    FilterAggregations,
    FilterClear,
    BrowseWhereTotals,
  }
})

export default class BrowseWhere extends Vue {
  utils = utils;
  data: any = null;
  view: any = false;
  showView: any = false;
  inMarkerView: boolean = false;
  mapUtils = mapUtils;

  created () {
    // set an initial search based on current route (while still updating url)
    searchModule.setSearch({
      ...router.currentRoute.query,
      ...{
        clear: true,
        forceReload: 'true',
        mapq: 'true',
        size: 500
      }
    });
  }

  markerViewEnter(el: any): void {
    el.style.height = `${ el.scrollHeight}px`;
  }

  markerViewLeave(el: any): void {
    el.style.height = '0px';
  }

  get isLoading(): boolean {
    return generalModule.getIsLoading;
  }

  get params(): any {
    return searchModule.getParams;
  }

  toggleView(view: any): void {
    this.showView = this.view !== view || !this.showView ? true : false;

    this.view = view;
  }

  toSearch () {
    searchModule.setSearch({ path: '/search', mapq: null});
  }

}
</script>
