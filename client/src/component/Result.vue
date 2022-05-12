<template>
	<div class="pt-3x px-base max-w-screen-xl mx-auto overflow-x-hidden flex flex-col lg:flex-row min-h-360 hg:min-h-300">
    <!-- filters (mobile & desktop) -->
    <template v-if="window.innerWidth">
      <filter-toggleable v-if="window.innerWidth < 1000" class="lg:w-1/3 lg:pr-lg">
        <filter-list :show="['search', 'miniMap', 'timeLine', 'aggregations']" />
      </filter-toggleable>
      <aside v-else class="lg:w-1/3 lg:pr-lg">
        <filter-list :show="['search', 'miniMap', 'timeLine', 'aggregations']" />
      </aside>
    </template>

    <!-- main content -->
    <section class="lg:w-2/3 lg:pl-lg block">
      <result-info class="py-base pt-none" />

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

<script setup lang="ts">
import { watch } from 'vue';
import { $computed } from 'vue/macros';
import { useRoute, onBeforeRouteLeave } from 'vue-router'
import { generalModule, searchModule } from "@/store/modules";

// base & utils
import utils from '@/utils/utils';

// general
import FilterList from '@/component/Filter/List.vue';
import FilterToggleable from '@/component/Filter/Toggleable.vue';

// unique
import ResultList from './Result/List.vue';
import ResultInfo from './Result/Info.vue';
import ResultPaginator from './Result/Paginator.vue';
import ResultSortOrder from './Result/SortOrder.vue';

let first: boolean = true;

const route = useRoute();
const window = $computed(() => generalModule.getWindow);
const params = $computed(() => searchModule.getParams);

const setMeta = () => {
  let title = 'Search';

  if (params.q) {
    title = `Search results: ${ params.q }`;
  }
  if (parseInt(params.page) > 1) {
    title += ` (page ${ params.page })`;
  }

  generalModule.setMeta({
    title: title,
    description: title,
  });
}

const unwatch = watch(route, async (path: any) => {
  if (first) {
    searchModule.actionResetResultState();
    first = false;
  }
  await searchModule.setSearch({ fromRoute: true });
  setMeta();

}, { immediate: true });

onBeforeRouteLeave(unwatch);
</script>
