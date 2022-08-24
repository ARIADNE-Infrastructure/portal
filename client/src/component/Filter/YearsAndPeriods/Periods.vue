<template>
  <div v-if="regions">
    <filter-aggregation
      key="countries"
      id="temporalRegion"
      :item="regions"
      :shortSortNames="true"
      sortKey="key"
      sortOrder="desc"
      docCountSuffix="periods"
    />

    <filter-aggregation
      v-if="periods"
      class="-mb-md"
      key="title"
      id="filterByCulturalPeriods"
      :item="periods"
      :shortSortNames="true"
      sortKey="key"
      sortKeyOption="start"
      sortKeyOptionLabel="Start year"
      sortOrder="desc"
      :sentenceCaseFilterText="false"
    />
  </div>
</template>

<script setup lang="ts">
import { onMounted, watch } from 'vue';
import { $computed, $$ } from 'vue/macros';
import { searchModule, periodsModule } from "@/store/modules";
import FilterAggregation from '@/component/Filter/Aggregation.vue';

const params = $computed(() => searchModule.getParams);
const regions = $computed(() => periodsModule.getRegions);
const periods = $computed(() => periodsModule.getPeriods);

onMounted(async () => {
  await periodsModule.setRegions();
  await periodsModule.setPeriods(params);
});

watch($$(params), async () => {
  await periodsModule.setPeriods(params);
});
</script>
