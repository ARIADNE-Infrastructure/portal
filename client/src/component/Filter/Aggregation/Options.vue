<template>
  <div
    v-if="options && id !== 'resourceType'"
    class="xl:flex p-md"
  >
    <!-- filter by text -->
    <input
      type="text"
      :placeholder="getFilterPlaceholder(title)"
      class="flex-1 w-full py-xs px-sm text-mmd outline-none border-base xl:border-r-0 border-gray focus:border-blue disabled:bg-white"
      v-model="options.search"
      :disabled="isLoading"
      @input="debouncedSearch($event)"
    >

    <div class="flex mt-md xl:mt-none">
      <!-- sort by name -->
      <button
        type="button"
        @click="setSort(sortKey || 'key')"
        class="text-white hover:bg-blue-90 px-sm py-xs text-md group transition-all duration-300 focus:outline-none flex-1 xl:flex-initial"
        :class="{ 'bg-blue': options.sortBy === (sortKey || 'key'), 'bg-blue-50': options.sortBy !== (sortKey || 'key') }"
      >
        <span class="pr-xs">{{ name }}</span>
        <i
          v-if="options.sortBy === (sortKey || 'key')"
          class="fa-chevron-down fas mr-sm duration-300 text-sm"
          :class="{ 'transform rotate-180': options.sortOrder === 'asc' }"
        />
      </button>

      <!-- sort by sortkeyoption -->
      <button v-if="sortKeyOption"
        type="button"
        @click="setSort(sortKeyOption)"
        class="text-white hover:bg-blue-90 px-sm py-xs text-md group transition-all duration-300 focus:outline-none flex-1 xl:flex-initial"
        :class="{ 'bg-blue': options.sortBy === sortKeyOption, 'bg-blue-50': options.sortBy !== sortKeyOption }"
      >
        <span class="pr-xs">{{ sortKeyOptionLabel }}</span>
        <i
          v-if="options.sortBy === sortKeyOption"
          class="fa-chevron-down fas mr-sm duration-300 text-sm"
          :class="{ 'transform rotate-180': options.sortOrder === 'asc' }"
        />
      </button>

      <!-- sort by # of results -->
      <button
        type="button"
        @click="setSort('doc_count')"
        class="text-white hover:bg-blue-90 px-sm py-xs text-md group transition-all duration-300 focus:outline-none flex-1 xl:flex-initial"
        :class="{ 'bg-blue': options.sortBy === 'doc_count', 'bg-blue-50': options.sortBy !== 'doc_count' }"
      >
        <span class="pr-xs">{{ hits }}</span>
        <i
          v-if="options.sortBy === 'doc_count'"
          class="fa-chevron-down fas mr-sm duration-300 text-sm"
          :class="{ 'transform rotate-180': options.sortOrder === 'asc' }"
        />
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { watch, nextTick } from 'vue';
import { onBeforeRouteLeave } from 'vue-router'
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
  getMore?: number
}>();

let filterSize: number = 0;

const params = $computed(() => searchModule.getParams);
const options = $computed(() => aggregationModule.getOptions[props.id]);
const isLoading: boolean = $computed(() => generalModule.getIsLoading);
const name: string = $computed(() => props.shortSortNames ? 'Name' : 'Sort by Name');
const hits: string = $computed(() => props.shortSortNames ? 'Hits' : 'Sort by Hits');
const getMore: number | undefined = $computed(() => props.getMore);

const getFilterPlaceholder = (title: string | undefined): string => {
  title = (title || '').trim();

  if (title && title[title.length - 1] !== 's') {
    title += 's';
  }
  return 'Enter text to filter on' + (title ? ' ' + title : '') + '.';
}

const setSearch = (): void => {
  nextTick(async () => {
    const payload = {
      id: props.id,
      value: {
        search: options?.search,
        sortBy: options?.sortBy,
        sortOrder: options?.sortOrder,
        data: {},
        size: filterSize,
      },
    };

    await aggregationModule.setSearch(payload);

    // emit list change event in order for accordion component to update its height
    emit('searchUpdate');
  });
}

const debouncedSearch = () => {
  filterSize = 0;
  utils.debounce('optionsSearch', setSearch, 600);
};

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

const watches = [
  watch($$(getMore), () => {
    filterSize++;
    setSearch();
  }),

  watch($$(options), () => {
    if (!options) {
      aggregationModule.setOptions({
        id: props.id,
        value: {
          search: '',
          sortBy: props.sortKeyOption || 'doc_count',
          sortOrder: props.sortOrder || 'desc',
          data: {},
          size: filterSize,
        }
      });
    }
  }, { immediate: true })
];

onBeforeRouteLeave(() => watches.forEach(unwatch => unwatch()));
</script>
