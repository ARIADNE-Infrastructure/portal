<template>
  <div
    class="fixed top-0 left-0 w-full h-full transition-opacity duration-300 cursor-default flex justify-center items-center"
    :class="active ? ('z-30 opacity-100' + (blocking ? '' : ' pointer-events-none')) : 'z-neg10 opacity-0'"
  >
    <div>
      <i class="fas fa-spinner fa-spin mr-sm text-2x text-darkGray"></i>
    </div>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { generalModule, searchModule } from "@/store/modules";
import router from '@/router';

const active: boolean = $computed(() => generalModule.getIsLoading || searchModule.getIsAggsLoading);
const blocking: boolean = $computed(() => router.currentRoute.value.path !== '/browse/where');
</script>
