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
import { Component, Vue } from 'vue-property-decorator';
import { search } from "@/store/modules";

@Component
export default class FilterClear extends Vue {
  get hasParams(): boolean {
    return search.hasParams;
  }

  clearFilters(): void {
    search.setSearch({ clear: true });
  }
}
</script>