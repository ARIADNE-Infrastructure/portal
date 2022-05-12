<template>
	<div class="pt-3x px-base max-w-screen-xl mx-auto text-mmd">
    <h1 class="text-2xl mb-xl">
      Browse the Catalogue
    </h1>

    <div class="flex flex-col md:flex-row pt-xs text-mmd">
      <b-link
        v-for="(link, key) in links"
        :key="key"
        :to="`/browse/${ link.id }`"
        class="w-full lg:w-1/3 relative mb-2x md:mb-none group"
        :class="{ 'md:mr-2x': !key, 'md:ml-2x': key === links.length - 1 }"
      >
        <span class="absolute z-10 top-sm left-sm bg-white px-md py-sm border-base border-blue transition-all duration-300 group-hover:bg-blue group-hover:text-white">
          <i class="mr-sm" :class="link.icon"></i>
          {{ utils.sentenceCase(link.id) }}
        </span>
        <img :src="`${ assets }/frontpage/${ link.id }.png`" :alt="link.id" class="w-full transition-opacity duration-300 group-hover:opacity-80 backface-hidden">
      </b-link>
    </div>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { generalModule } from "@/store/modules";
import BLink from '@/component/Base/Link.vue';
import utils from '../utils/utils';

const links = $computed(() => generalModule.getFrontPageLinks);
const assets: string = $computed(() => generalModule.getAssetsDir);
</script>
