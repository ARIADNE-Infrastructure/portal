<template>
  <list-tabs :initiallySelectedTabTitle="initiallySelectedTabTitle">
    <list-tab
      title="Filter By Year"
      icon="fa fa-calendar-alt"
    >
      <p
        class="bg-red text-white text-md rounded-base ease-in-out duration-200 overflow-hidden"
        :class="{
          'h-none': !activePeriodFiltersWarning,
          'p-sm mb-md': activePeriodFiltersWarning,
        }"
      >
        {{ activePeriodFiltersWarning }}
      </p>

      <filter-years />
    </list-tab>

    <list-tab
      title="Filter By Time Periods"
      icon="fa fa-history"
    >
      <p
        class="bg-red text-white text-md rounded-base ease-in-out duration-200 overflow-hidden"
        :class="{
          'h-none': !activeYearsFilterWarning,
          'p-sm mb-md': activeYearsFilterWarning,
        }"
      >
        {{ activeYearsFilterWarning }}
      </p>

      <filter-periods />
    </list-tab>
  </list-tabs>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { searchModule } from "@/store/modules";
import ListTabs from '@/component/List/Tabs.vue';
import ListTab from '@/component/List/Tab.vue';
import FilterYears from '@/component/Filter/YearsAndPeriods/Years.vue';
import FilterPeriods from '@/component/Filter/YearsAndPeriods/Periods.vue';

const params = $computed(() => searchModule.getParams);

const initiallySelectedTabTitle: string = $computed(() => {
  return params?.publisher || params?.ariadneSubject ? 'Filter By Time Periods' : 'Filter By Year';
});

const activeYearsFilterWarning: string = $computed(() => {
  if (params?.range) {
    return 'Your current search is filtering on Years. This filter will be reseted if you use the filters below.';
  }
  return '';
});

const activePeriodFiltersWarning: string = $computed(() => {
  if (params?.publisher || params?.ariadneSubject) {
    return 'Your current search is filtering on Time Periods. These filters will be reseted if you use the fields below.';
  }
  return '';
});
</script>
