<template>
  <button
    v-if="hasParams"
    class="p-md bg-red text-white text-md hover:bg-red-90 focus:outline-none transition-bg duration-300 w-full block text-center rounded-base"
    @click="clearFilters"
  >
    <i class="fas fa-times mr-xs"></i>
    Clear All Filters
  </button>
</template>

<script lang="ts">
import { Component, Vue, Prop } from 'vue-property-decorator';
import { searchModule, aggregationModule } from "@/store/modules";
import utils from '@/utils/utils';

@Component
export default class FilterClear extends Vue {
  @Prop() ignoreParams!: string[];

  get params(): any {
    return searchModule.getParams;
  }

  get hasParams(): boolean {
    return !utils.objectEquals(this.params, { q: ''}, this.ignoreParams)
  }

  clearFilters(): void {
    let clearParams: any = {clear: true};

    if (this.params?.mapq) {
      clearParams.mapq = true;
    }

    searchModule.setSearch(clearParams);
    aggregationModule.setOptionsToDefault();
  }
}
</script>