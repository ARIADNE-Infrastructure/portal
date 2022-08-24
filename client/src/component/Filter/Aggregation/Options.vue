<template>
  <div
    v-if="options"
    class="xl:flex p-md"
  >
    <!-- filter by text -->
    <input
      type="text"
      :placeholder="getFilterPlaceholder(title)"
      class="flex-1 w-full py-xs px-sm text-mmd outline-none border-base rounded-base xl:rounded-r-0 xl:border-r-0 border-gray focus:border-blue disabled:bg-white"
      v-model="localSearch"
      :disabled="isLoading"
      @input="debouncedSearch($event)"
    >

    <div class="flex mt-md xl:mt-none">
      <!-- sort by name -->
      <button
        type="button"
        @click="setSort(sortKey || 'key')"
        class="text-white hover:bg-blue-90 px-sm py-xs text-md group transition-all duration-300 focus:outline-none rounded-l-base xl:rounded-l-0 flex-1 xl:flex-initial"
        :class="{ 'bg-blue': options.sortBy !== 'doc_count', 'bg-blue-50': options.sortBy === 'doc_count' }"
      >
        <span class="pr-xs">{{ name }}</span>
        <i
          v-if="options.sortBy === 'key'"
          class="fa-chevron-down fas mr-sm duration-300 text-sm"
          :class="{ 'transform rotate-180': options.sortOrder === 'asc' }"
        />
      </button>

      <!-- sort by # of results -->
      <button
        type="button"
        @click="setSort(sortKeyOption || 'doc_count')"
        class="text-white hover:bg-blue-90 px-sm py-xs text-md group transition-all duration-300 focus:outline-none rounded-base rounded-l-0 flex-1 xl:flex-initial"
        :class="{ 'bg-blue': options.sortBy === 'doc_count', 'bg-blue-50': options.sortBy !== 'doc_count' }"
      >
        <span class="pr-xs">{{ sortKeyOptionLabel || hits }}</span>
        <i
          v-if="(options.sortBy !== 'key')"
          class="fa-chevron-down fas mr-sm duration-300 text-sm"
          :class="{ 'transform rotate-180': options.sortOrder === 'asc' }"
        />
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { watch } from 'vue';
import { $ref, $computed, $$ } from 'vue/macros';
import { generalModule, searchModule, aggregationModule } from "@/store/modules";
import utils from '@/utils/utils';

const emit = defineEmits(['searchUpdate']);

const props = defineProps<{
  id: string,
  shortSortNames?: boolean,
  sortKey?: string,
  sortKeyOption?: string, // Secondary sorting apart from default key and aggs doc_count attribute. If set, it's used instead of sortKey
  sortKeyOptionLabel?: string, // Secondary sorting label apart from default 'Hits'. If set, it's used instead of 'Hits'
  sortOrder?: string,
  title?: string,
}>();

let localSearch: string = $ref('');

const params = $computed(() => searchModule.getParams);
const options = $computed(() => aggregationModule.getOptions[props.id]);
const isLoading: boolean = $computed(() => generalModule.getIsLoading);
const name: string = $computed(() => props.shortSortNames ? 'Name' : 'Sort by Name');
const hits: string = $computed(() => props.shortSortNames ? 'Hits' : 'Sort by Hits');

const getFilterPlaceholder = (title: string | undefined): string => {
  title = (title || '').trim().toLowerCase();
  if (title && title[title.length - 1] !== 's') {
    title += 's';
  }
  return 'Filter' + (title ? ' ' + title : '') + '..';
}

const setSearch = (): void => {
  const payload = {
    id: props.id,
    value: {
      search: localSearch,
      sortBy: options.sortBy,
      sortOrder: options.sortOrder,
      data: {},
    },
  }

  aggregationModule.setSearch(payload);

  // emit list change event in order for accordion component to update its height
  emit('searchUpdate');
}

const debouncedSearch = () => utils.debounce('optionsSearch', setSearch, 600);

const setSort = (sortBy: string): void => {
  const payload = {
    id: props.id,
    value: {
      search: options.search,
      sortBy: '',
      sortOrder: '',
      data: options.data,
    },
  }

  payload.value.sortBy = sortBy;
  
  if (options.sortBy === sortBy) {
    // toggle current order
    payload.value.sortOrder = options.sortOrder === 'asc' ? 'desc' : 'asc';
  } else {
    // if name, default to sorting a-z, if count, start with highest number
    payload.value.sortOrder = sortBy === 'doc_count' ? 'desc' : 'asc';
  }

  aggregationModule.setOptions(payload);
}

watch($$(options), () => {
  if (options) {
    localSearch = options.search;

  } else {
  // set default options if none already exists
    const payload = {
      id: props.id,
      value: {
        search: '',
        sortBy: props.sortKeyOption || 'doc_count',
        sortOrder: props.sortOrder || 'desc',
        data: {},
      }
    }

    aggregationModule.setOptions(payload);
  }
}, { immediate: true });

watch($$(params), () => {
  if (localSearch) {
    setSearch();
  }
}, { immediate: true });
</script>
