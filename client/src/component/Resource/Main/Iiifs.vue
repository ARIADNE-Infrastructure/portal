<template>
  <div v-if="iiifUrls.length">
    <h3 class="text-lg font-bold mb-lg">
      <i class="far fa-image mr-sm"></i> Iiif
    </h3>
    <ul class="w-full">
      <li v-for="(url, key) in iiifUrls" class="mb-xs" :key="key">
        <i class="fas fa-image mr-sm text-base" style="vertical-align:-1px"></i>
        <b-link :href="url.url" class="text-blue transition-colors duration-300 hover:text-darkGray hover:underline break-word" :class="url.download ? 'mr-sm' : ''" target="_blank">
          <span class="mr-xs">{{ url.name }}</span> <i class="fas fa-external-link-alt"></i>
        </b-link>
        <b-link v-if="url.download" :href="url.download" class="text-blue transition-colors duration-300 hover:text-darkGray" target="_blank" title="Download manifest">
          <i class="fas fa-cloud-download-alt"></i>
        </b-link>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { resourceModule } from "@/store/modules";
import BLink from '@/component/Base/Link.vue';

const resource = $computed(() => resourceModule.getResource);
const iiifUrls = $computed(() => resourceModule.getIiifUrls(resource));
</script>
