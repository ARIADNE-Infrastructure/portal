<template>
  <div v-if="!loaded">
    Loading..
  </div>
  <div v-else-if="regions">
    <filter-aggregation
      key="countries"
      id="temporalRegion"
      :item="regions"
      :shortSortNames="true"
      sortKey="key"
      sortOrder="desc"
      docCountSuffix="periods"
      :maxHeight="1000"
    />

    <filter-aggregation
      v-if="periods"
      class="-mb-md"
      key="key"
      id="culturalPeriods"
      :item="periods"
      :shortSortNames="true"
      sortKey="filterLabel"
      sortKeyOption="start"
      sortKeyOptionLabel="Start year"
      sortOrder="asc"
      :sentenceCaseFilterText="false"
      :maxHeight="1000"
    />
  </div>
  <div v-else>
    Failed to get regions &amp; periods.
  </div>
</template>

<script setup lang="ts">
import { onMounted, watch } from 'vue';
import { $computed, $$ } from 'vue/macros';
import { searchModule, periodsModule } from "@/store/modules";
import { onBeforeRouteLeave } from 'vue-router'
import FilterAggregation from '@/component/Filter/Aggregation.vue';

const params = $computed(() => searchModule.getParams);
const regions = $computed(() => periodsModule.getRegions);
const periods = $computed(() => periodsModule.getPeriods);
const loaded = $computed(() => periodsModule.getLoaded);
let lastTemporal: any;

onMounted(async () => {
  await periodsModule.setRegions();
  lastTemporal = params.temporalRegion;
  await periodsModule.setPeriods(params);
  periodsModule.setLoaded();
});

const unwatch = watch($$(params), () => {
  if (lastTemporal !== params.temporalRegion) {
    lastTemporal = params.temporalRegion;
    periodsModule.setPeriods(params);
  }
});
onBeforeRouteLeave(unwatch);
</script>
