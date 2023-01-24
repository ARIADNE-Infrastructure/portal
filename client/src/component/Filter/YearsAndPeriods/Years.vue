<template>
  <div class="flex">
    <input
      v-model="yearFrom"
      class="p-sm text-md outline-none focus:border-blue border-base border-gray disabled:bg-white w-1/2"
      type="text"
      placeholder="Year (from)"
      :disabled="isLoading"
      @keydown.enter.prevent="validateAndSearch"
    />

    <input
      v-model="yearTo"
      class="p-sm text-md outline-none focus:border-blue border-base border-gray disabled:bg-white w-1/2"
      type="text"
      placeholder="Year (to)"
      :disabled="isLoading"
      @keydown.enter.prevent="validateAndSearch"
    />

    <button
      class="bg-blue-50 px-md py-sm text-center text-md text-white cursor-pointer hover:bg-blue transition-color duration-300"
      @click.prevent="validateAndSearch">
      Apply
    </button>
  </div>
</template>

<script setup lang="ts">
import { watch } from 'vue';
import { $ref, $computed, $$ } from 'vue/macros';
import { generalModule, searchModule } from "@/store/modules";

let yearFrom = $ref(null);
let yearTo = $ref(null);

const isLoading = $computed(() => generalModule.getIsLoading);
const params = $computed(() => searchModule.getParams);

const validateAndSearch = (event: any) => {
  if (validateYear(event)) {
    setSearch();
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
      range: `${yearFrom},${yearTo}`,
      temporalRegion: '',
      culturalPeriods: '',
      culturalLabels: '',
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
