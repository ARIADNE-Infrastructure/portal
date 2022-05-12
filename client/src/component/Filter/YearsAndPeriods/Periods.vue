<template>
  <div v-if="regions && periods">
    <!-- todo: switch to periodo regions when available -->
    <filter-aggregation
      key="regions"
      id="publisher"
      :item="regions"
      :shortSortNames="true"
    />

    <!-- todo: switch to periods when available -->
    <filter-aggregation
      class="-mb-md"
      key="periods"
      id="ariadneSubject"
      :item="periods"
      :shortSortNames="true"
    />
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { aggregationModule } from "@/store/modules";
import FilterAggregation from '@/component/Filter/Aggregation.vue';

// todo: switch to periodo filters when available
const regions = $computed(() => aggregationModule.getSorted.publisher);
const periods = $computed(() => aggregationModule.getSorted.ariadneSubject);
</script>
