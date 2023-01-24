<template>
  <button
    v-if="hasParams"
    class="p-md bg-red text-white text-md hover:bg-red-90 focus:outline-none transition-bg duration-300 w-full block text-center"
    @click.prevent="clearFilters"
  >
    Clear All Filters
    <i class="fas fa-times ml-sm"></i>
  </button>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { searchModule, aggregationModule } from "@/store/modules";
import utils from '@/utils/utils';

const props = defineProps<{
  ignoreParams: Array<string>,
}>();

const params = $computed(() => searchModule.getParams);
const hasParams: boolean = $computed(() => !utils.objectEquals(params, { q: ''}, props.ignoreParams))

const clearFilters = () => {
  const clearParams: any = { clear: true };

  if (params?.mapq) {
    clearParams.mapq = true;
  }

  searchModule.setSearch(clearParams);
  aggregationModule.setOptionsToDefault();
}
</script>
