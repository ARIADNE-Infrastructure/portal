<template>
	<div>
    <!-- <browse-where-map
      class="absolute left-0 w-full"
      style="height:calc(100vh - 11rem)"
      height="calc(100vh - 11rem)"
      @mapChange="mapChange"
    /> -->

    <browse-where-map-old
      class="absolute left-0 w-full"
      :isMultiple="true"
      height="calc(100vh - 12rem)"
      @mapChange="mapChange"
    />

    <div class="relative">
      <div class="absolute w-toolbar top-2xl right-0 text-center shadow-full z-1001">
        <!-- totals -->
        <browse-where-totals :data="data" />

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
                      bg="bg-blue"
                      hover="hover:bg-blue-80"
                      focus="focus:border-blue"
                      class="mb-lg"
                      :breakHg="true"
                      :big="true"
                      :useCurrentSearch="true"
                      :showFields="true"
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

              <filter-clear class="rounded-bl-0 rounded-t-0" />
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
import { search, general } from "@/store/modules";
import utils from '@/utils/utils';

import BrowseWhereMap from './BrowseWhere/Map.vue';
import BrowseWhereMapOld from './BrowseWhere/Map.vue';
import FilterSearch from '@/component/Filter/Search.vue';
import FilterYears from '@/component/Filter/Years.vue';
import FilterAggregations from '@/component/Filter/Aggregations.vue';
import FilterClear from '@/component/Filter/Clear.vue';
import BrowseWhereTotals from './BrowseWhere/Totals.vue';

@Component({
  components: {
    BrowseWhereMap,
    BrowseWhereMapOld,
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

  created () {
    search.setSearch({
      fromRoute: true
    });
  }

  get isLoading(): boolean {
    return general.getLoading;
  }

  get params(): any {
    return search.getParams;
  }

  toggleView(view: any): void {
    this.showView = this.view !== view || !this.showView ? true : false;

    this.view = view;
  }

  mapChange (data: any) {
    this.data = data;
  }

  toSearch () {
    search.setSearch({ path: '/search', mapq: null});
  }
}
</script>
