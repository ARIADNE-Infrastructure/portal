<template>
  <div>
    <h2 class="text-lg mb-md">
      Filters
    </h2>

    <filter-search
      v-if="getShow('search')"
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

    <filter-clear
      :ignoreParams="['page', 'sort', 'order', 'mapq', 'size', 'loadingStatus', 'forceReload']"
      class="mb-lg"
    />

    <filter-years-and-periods
      v-if="getShow('yearsAndPeriods')"
      class="mb-lg"
    />

    <filter-mini-map
      v-if="getShow('miniMap')"
      title="Where"
      class="mb-lg"
    />

    <filter-time-line
      v-if="getShow('timeLine') && utils.objectIsNotEmpty(result) && result.hits && result.hits.length"
      :hasLoader="true"
      title="When"
      class="mb-lg"
    />

    <template v-if="getShow('aggregations')">
      <filter-aggregation
        v-for="(item, id) in sortedAggs"
        :key="id"
        :id="id"
        :item="item"
        :shortSortNames="true"
        :maxHeight="1000"
      />
    </template>
  </div>
</template>

<script setup lang="ts">
import { watch } from 'vue';
import { $computed } from 'vue/macros';
import { useRoute, onBeforeRouteLeave } from 'vue-router'
import { searchModule, aggregationModule } from "@/store/modules";
import utils from '@/utils/utils';
import router from '@/router';
import FilterSearch from '@/component/Filter/Search.vue';
import FilterYearsAndPeriods from '@/component/Filter/YearsAndPeriods.vue';
import FilterClear from '@/component/Filter/Clear.vue';
import FilterMiniMap from '@/component/Filter/MiniMap.vue';
import FilterTimeLine from '@/component/Filter/TimeLine.vue';
import FilterAggregation from '@/component/Filter/Aggregation.vue';

const props = defineProps<{
  show: Array<string>,
}>();

let isUnmounted: boolean = false;
const route = useRoute();
const result = $computed(() => searchModule.getResult);
const sortedAggs = $computed(() => aggregationModule.getSorted);

const getShow = (component: string): boolean => {
  return !!props.show?.includes(component);
}

watch(route, () => {
  if (!isUnmounted && router.currentRoute.value.path !== '/browse/where') {
    searchModule.setAggregationSearch(router.currentRoute.value.query);
  }
}, { immediate: true })

onBeforeRouteLeave(() => isUnmounted = true);
</script>
