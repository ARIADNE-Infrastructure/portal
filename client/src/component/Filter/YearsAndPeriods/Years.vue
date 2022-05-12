<template>
  <div class="flex">
    <input
      v-model="yearFrom"
      class="p-sm text-md outline-none focus:border-blue border-base border-gray rounded-l-base disabled:bg-white w-1/2"
      type="text"
      placeholder="Year (from)"
      :disabled="isLoading"
      @input="validateAndSearch"
    />

    <input
      v-model="yearTo"
      class="p-sm text-md outline-none focus:border-blue border-base border-gray rounded-r-base disabled:bg-white w-1/2"
      type="text"
      placeholder="Year (to)"
      :disabled="isLoading"
      @input="validateAndSearch"
    />
  </div>
</template>

<script setup lang="ts">
import { watch } from 'vue';
import { $ref, $computed, $$ } from 'vue/macros';
import { generalModule, searchModule } from "@/store/modules";
import utils from '@/utils/utils';

let yearFrom = $ref(null);
let yearTo = $ref(null);

const isLoading = $computed(() => generalModule.getIsLoading);
const params = $computed(() => searchModule.getParams);

const validateAndSearch = (event: any) => {
  if (validateYear(event)) {
    utils.debounce('yearsSearch', setSearch, 1000);
  }
};

const validateYear = (event: any): boolean => {
  const pattern = /^\-?[0-9]*$/;
  return pattern.test(event.target.value);
};

const setSearch = () => {
  // clear range param is both years are empty
  if (!yearFrom && !yearTo) {
    searchModule.setSearch({
      range: null,
    });
  }

  else if (yearTo && yearFrom && parseInt(yearTo) >= parseInt(yearFrom)) {
    searchModule.setSearch({
      range: `${yearFrom},${yearTo}`
    });
  }
};

// keep local vars synced with store state
watch($$(params), () => {
  if (params.range) {
    const years = params.range.split(',');

    if (parseInt(years[0])) {
      yearFrom = years[0];
    }
    if (parseInt(years[1])) {
      yearTo = years[1];
    }
  } else {
    yearFrom = yearTo = null;
  }
}, { immediate: true });
</script>
