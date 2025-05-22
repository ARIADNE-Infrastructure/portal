<!-- Front page -->
<template>
  <div>
    <h2 class="text-2xl">
      Results
      <span v-if="searchQuery">for "{{ searchQuery }}"</span>
    </h2>

    <div v-if="result && result.total" class="text-md">
      Time: {{ result.time }}s.
      Total: {{ new Intl.NumberFormat('en',{style: 'decimal'}).format(result.total.value) }}<span v-if="result.total.relation !== 'eq'">+</span>.
      <span v-if="result.total.value">
        Page: {{ currentPage }} / {{ lastPage }}
      </span>
    </div>
    <div v-if="activeFilters.length && !hideFilters">
      <div v-for="(filter, key) in activeFilters" :key="key" @click="removeFilter(filter)"
        class="inline-block bg-lightGray mr-md mt-md py-xs px-sm cursor-pointer hover:bg-red-80 group transition-bg duration-300">
        <span class="group-hover:text-white transition-text duration-300 text-mmd align-middle">
          {{ getFilterTitle(filter) }}:
          {{ getFilterValue(filter) }}
        </span>
        <i class="fas fa-times text-md text-red group-hover:text-white align-middle ml-sm transition-text duration-300"></i>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { searchModule, aggregationModule } from "@/store/modules";
import { iKeyVal } from '@/store/modules/Aggregation';
import utils from '@/utils/utils';

defineProps<{
  hideFilters?: boolean
}>();

const params = $computed(() => searchModule.getParams);
const result = $computed(() => searchModule.getResult);
const perPage = $computed(() => parseInt(searchModule.getPerPage));
const activeFilters = $computed(() => aggregationModule.activeFilters);
const searchQuery: string = $computed(() => activeFilters.find((f: any) => f.key === 'q')?.val || '');

const currentPage: number = $computed(() => {
  let p = parseInt(params.page);
  return p && p > 1 ? p : 1;
});

const lastPage: number = $computed(() => Math.ceil(result.total.value / perPage));

const getFilterTitle = (filter: iKeyVal): string => {
  if (filter.key === 'q') {
    return 'Search';
  }
  return aggregationModule.getTitle(filter.key);
};

const getFilterValue = (filter: iKeyVal): string => {
  const filterVal = filter.val || '';
  if (filter.key === 'bbox') {
    return filterVal.split(',').map(v => Math.round(parseFloat(v.trim()))).join(',');
  }
  if (filter.key === 'isPartOf') {
    return params.isPartOfLabel || filterVal
  }
  if (filter.key === 'temporalRegion' || filter.key === 'dataType' || filter.key === 'placeName') {
    return utils.sentenceCase(filterVal);
  }
  if (filter.key === 'culturalPeriods' && params.culturalLabels) {
    const label = params.culturalLabels.split('|').find((l: string) => filterVal === l.split(':')[0]);
    if (label) {
      return utils.sentenceCase(label.split(':')[1] || '');
    }
  }
  if (filter.key === 'range') {
    return filterVal.replace(',', ', ');
  }
  if (filterVal.length) {
    return utils.ucFirst(filterVal);
  }
  return filterVal;
};

const removeFilter = (filter: iKeyVal) => {
  if (filter.key === 'isPartOf') {
      delete params.isPartOfLabel;
      searchModule.updateParams(params);
  } else if (filter.key === 'bbox') {
    delete params.ghp;
    searchModule.updateParams(params);
  }
  aggregationModule.setActive({
    key: filter.key,
    value: filter.val,
    add: false,
  });
};
</script>
