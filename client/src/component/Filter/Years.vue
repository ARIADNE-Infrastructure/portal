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

<script lang="ts">
import { Component, Vue, Watch } from 'vue-property-decorator';
import debounce from 'debounce';
import { generalModule, searchModule } from "@/store/modules";

@Component
export default class FilterYears extends Vue {
  private yearFrom: number | null = null;
  private yearTo: number | null = null;
  private debouncedSearch: any = debounce(this.setSearch, 600);

  get isLoading(): boolean {
    return generalModule.getIsLoading;
  }

  get params(): any {
    return searchModule.getParams;
  }

  validateAndSearch(event: any) {
    if (this.validateYear(event)) {
      this.debouncedSearch(event);
    }
  }

  validateYear(event: any): boolean {
    const pattern = /^\-?[0-9]*$/;
    const isValid = pattern.test(event.target.value);

    if (!isValid) {
      const resetEvent = document.createEvent('Event');

      resetEvent.initEvent('input', true, true);
      event.target.value = event.target._value;
      event.target.dispatchEvent(resetEvent);

      return false;
    }

    return true;
  }

  setSearch() {
    // clear range param is both years are empty
    if (!this.yearFrom && !this.yearTo) {
      searchModule.setSearch({
        range: null,
      });
    }

    else if (this.yearTo) {
      searchModule.setSearch({
        range: `${this.yearFrom},${this.yearTo}`
      });
    }
  }

  // keep local vars synced with store state
  @Watch('params', { immediate: true })
  onParamsUpdate() {
    if (this.params.range) {
      const years = this.params.range.split(',');

      if (parseInt(years[0])) {
        this.yearFrom = years[0];
      }

      if (parseInt(years[1])) {
        this.yearTo = years[1];
      }
    }
    else {
      this.yearFrom = this.yearTo = null;
    }
  }
}
</script>
