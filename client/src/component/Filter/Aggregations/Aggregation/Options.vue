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
        @click="setSort('key')"
        class="text-white hover:bg-blue-90 px-sm py-xs text-md group transition-all duration-300 focus:outline-none rounded-l-base xl:rounded-l-0 flex-1 xl:flex-initial"
        :class="{ 'bg-blue': options.sortBy === 'key', 'bg-blue-50': options.sortBy !== 'key' }"
      >
        <span class="pr-xs">{{ name }}</span>
        <i
          v-if="options.sortBy === 'key'"
          class="fa-chevron-down fas mr-xs duration-300 text-sm"
          :class="{ 'transform rotate-180': options.sortOrder === 'asc' }"
        />
      </button>

      <!-- sort by # of results -->
      <button
        type="button"
        @click="setSort('doc_count')"
        class="text-white hover:bg-blue-90 px-sm py-xs text-md group transition-all duration-300 focus:outline-none rounded-base rounded-l-0 flex-1 xl:flex-initial"
        :class="{ 'bg-blue': options.sortBy === 'doc_count', 'bg-blue-50': options.sortBy !== 'doc_count' }"
      >
        <span class="pr-xs">{{ hits }}</span>
        <i
          v-if="options.sortBy === 'doc_count'"
          class="fa-chevron-down fas mr-xs duration-300 text-sm"
          :class="{ 'transform rotate-180': options.sortOrder === 'asc' }"
        />
      </button>
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Prop, Watch } from 'vue-property-decorator';
import { generalModule, searchModule, aggregationModule } from "@/store/modules";
import debounce from 'debounce';

@Component
export default class FilterAggregationOptions extends Vue {
  debouncedSearch: any = debounce(this.setSearch, 600);
  localSearch: string = '';

  @Prop() id!: string;
  @Prop() shortSortNames!: boolean;
  @Prop() title!: string;

  get isLoading(): boolean {
    return generalModule.getIsLoading;
  }

  get params(): any {
    return searchModule.getParams;
  }

  get options(): any {
    return aggregationModule.getOptionsById(this.id);
  }

  get name() {
    return this.shortSortNames ? 'Name' : 'Sort by Name';
  }

  get hits() {
    return this.shortSortNames ? 'Hits' : 'Sort by Hits';
  }

  getFilterPlaceholder (title: string | undefined) {
    title = (title || '').trim().toLowerCase();
    if (title && title[title.length - 1] !== 's') {
      title += 's';
    }
    return 'Filter' + (title ? ' ' + title : '') + '..';
  }

  setSearch(): void {
    const payload = {
      id: this.id,
      value: {
        search: this.localSearch,
        sortBy: this.options.sortBy,
        sortOrder: this.options.sortOrder,
        data: {},
      },
    }

    aggregationModule.setSearch(payload);

    // emit list change event in order for accordion component to update its height
    this.$emit('searchUpdate');
  }

  setSort(sortBy: string): void {
    const payload = {
      id: this.id,
      value: {
        search: this.options.search,
        sortBy: '',
        sortOrder: '',
        data: this.options.data,
      },
    }

    payload.value.sortBy = sortBy;

    if (this.options.sortBy === sortBy) {
      // toggle current order
      payload.value.sortOrder = this.options.sortOrder === 'asc' ? 'desc' : 'asc';
    }
    else {
      // if name, default to sorting a-z, if count, start with highest number
      payload.value.sortOrder = sortBy === 'key' ? 'asc' : 'desc';
    }

    aggregationModule.setOptions(payload);
  }

  @Watch('options', { immediate: true })
  onOptionsUpdate() {
    if (this.options) {
      this.localSearch = this.options.search;
    }
    else {
    // set default options if none already exists
      const payload = {
        id: this.id,
        value: {
          search: '',
          sortBy: 'doc_count',
          sortOrder: 'desc',
          data: {},
        }
      }

      aggregationModule.setOptions(payload);
    }
  }

  @Watch('params', { immediate: true })
  onParamsUpdate() {
    if (this.localSearch) {
      this.setSearch();
    }
  }
}
</script>
