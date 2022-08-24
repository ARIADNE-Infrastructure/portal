<template>
	<div class="text-center">
    <div
      v-if="result.error"
      class="bg-white p-md text-mmd border-b-base border-red rounded-tr-base text-red"
    >
      {{ result.error }}
    </div>

    <div class="bg-white p-md text-mmd rounded-tr-base">
      <div v-if="data === true">
        <i class="fas fa-circle-notch fa-spin mr-sm backface-hidden"></i>
        Loading..
      </div>
      <div v-else-if="data && data.error" class="text-red">
        Error: Failed to load map data
      </div>
      <div v-else>
        <b>{{ totals }}</b>
        resources in the current view
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { searchModule } from "@/store/modules";

const { data } = defineProps<{
  data: any
}>();
const result = $computed(() => searchModule.getAggsResult);

const totals: string = $computed(() => {
  let total = data ? (data?.result?.total) : (result?.total);
  let val = total?.value || 0;

  if (val && total?.relation !== 'eq') {
    val += '+';
  }
  return String(val).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
});
</script>
