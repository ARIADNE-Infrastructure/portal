<!-- Front page -->
<template>
  <div>
    <h2 class="text-2xl">
      Results
      <span v-if="searchQuery">for "{{ searchQuery }}"</span>
    </h2>

    <div v-if="result && result.total" class="text-md">
      Time: {{ result.time }}s.
      Total: {{ result.total.value }}<span v-if="result.total.relation !== 'eq'">+</span>.
      <span v-if="result.total.value">
        Page: {{ currentPage }} / {{ lastPage }}
      </span>
    </div>

    <div v-if="activeFilters.length">
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

const params = $computed(() => searchModule.getParams);
const result = $computed(() => searchModule.getResult);
const activeFilters = $computed(() => aggregationModule.activeFilters);
const searchQuery: string = $computed(() => activeFilters.find((f: any) => f.key === 'q')?.val || '');

const resultRange: string = $computed(() => {
  const start = currentPage < 2 ? 1 : (currentPage - 1) * 10 + 1;
  const end = (currentPage - 1) * 10 + result.hits.length;

  return `(${ start }-${ end })`;
});

const currentPage: number = $computed(() => {
  let p = parseInt(params.page);
  return p && p > 1 ? p : 1;
});

const lastPage: number = $computed(() => Math.ceil(result.total.value / 10));

const getFilterTitle = (filter: iKeyVal): string => {
  if (filter.key === 'q') {
    return 'Search';
  }
  return aggregationModule.getTitle(filter.key);
};

// for some params that are id:s sometimes a label param is present
const getLabelParam = (filter: iKeyVal): string => {
  const labels = ['isPartOf', 'derivedSubjectId'];
  if (labels.includes(filter.key)) {
    return params[filter.key + 'Label'] || '';
  }
  return '';
};

const getFilterValue = (filter: iKeyVal): string => {
  return getLabelParam(filter) || filter.val;
};

const removeFilter = (filter: iKeyVal) => {
  if (getLabelParam(filter)) {
      delete params[filter.key + 'Label'];
      searchModule.updateParams(params);
  }
  aggregationModule.setActive({
    key: filter.key,
    value: filter.val,
    add: false,
  });
};
</script>
