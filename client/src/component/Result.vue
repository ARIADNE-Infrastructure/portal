<template>
	<div class="pt-3x px-base max-w-screen-xl mx-auto overflow-x-hidden flex flex-col lg:flex-row min-h-360 hg:min-h-300">
    <!-- sidebar -->
    <aside class="lg:w-1/3 lg:pr-lg block order-last lg:order-first">
      <result-sidebar />
    </aside>

    <!-- main content -->
    <section class="lg:w-2/3 lg:pl-lg block">
      <result-info class="py-base pt-none" />

      <div class="lg:hidden">
        <filter-search
          color="blue"
          hoverStyle="hover:bg-blue-80"
          focusStyle="focus:border-blue"
          class="mt-sm mb-xl"
          :big="true"
          :useCurrentSearch="true"
          :clearSearch="true"
          :hasAutocomplete="true"
          showFields="select"
          @submit="utils.blurMobile()"
        />
      </div>

      <div class="md:flex mt-sm items-center justify-between pt-none">
        <result-paginator />
        <result-sort-order class="mt-lg md:mt-none" />
      </div>

      <div class="mb-base mt-xl">
        <result-list />
      </div>

      <result-paginator class="pt-base" :scrollTop="true" />
    </section>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Watch } from 'vue-property-decorator';
import { generalModule, searchModule } from "@/store/modules";

// base & utils
import utils from '@/utils/utils';

// general
import FilterSearch from '@/component/Filter/Search.vue';

// unique
import ResultList from './Result/List.vue';
import ResultInfo from './Result/Info.vue';
import ResultPaginator from './Result/Paginator.vue';
import ResultSidebar from './Result/Sidebar.vue';
import ResultSortOrder from './Result/SortOrder.vue';

@Component({
  components: {
    FilterSearch,
    ResultList,
    ResultInfo,
    ResultPaginator,
    ResultSidebar,
    ResultSortOrder,
  }
})
export default class Result extends Vue {
  utils = utils;

  get params(): any {
    return searchModule.getParams;
  }

  setMeta () {
    let title = 'Search';

    if (this.params.q) {
      title = `Search results: ${ this.params.q }`;
    }
    if (parseInt(this.params.page) > 1) {
      title += ` (page ${ this.params.page })`;
    }

    generalModule.setMeta({
      title: title,
      description: title,
    });
  }

  @Watch('$route', { immediate: true })
  async onRouteUpdate () {
    await searchModule.setSearch({ fromRoute: true });
    this.setMeta();
  }
}
</script>