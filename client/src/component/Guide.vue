<template>
	<div class="pt-3x px-base max-w-screen-xl mx-auto text-mmd">
    <h1 class="text-2xl mb-md">ARIADNE portal guide</h1>
    <p class="mb-md">
      The guide can be found here:
      <b-link :href="`${assets}/about/using-the-ariadne-portal.pdf`" target="_blank" :useDefaultStyle="true">Using the ARIADNE Portal guide</b-link>.
    </p>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { generalModule } from "@/store/modules";
import BLink from '@/component/Base/Link.vue';

const assets: string = $computed(() => generalModule.getAssetsDir);
</script>
