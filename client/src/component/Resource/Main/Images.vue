<template>
  <div
    :class="{
      'mb-3x': digitalImages.length > 3,
      'md:mb-3x': digitalImages.length > 6
    }"
  >
    <h3 class="text-lg font-bold mb-lg">
      <i class="far fa-image mr-sm"></i> Images
    </h3>

    <div
      class="-mx-xs"
      :class="{
        'px-xl': digitalImages.length > 3,
        'md:px-xl': digitalImages.length > 6
      }"
    >
      <carousel :items-to-show="1.2">
        <slide v-for="(url, key) in digitalImages" :key="key">
          <img
            :src="url" alt="digital image"
            class="border-base border-blue opacity-60 hover:opacity-100 transition-opacity duration-300"
          >
        </slide>
        <template #addons>
          <navigation />
          <pagination />
        </template>
      </carousel>
    </div>
  </div>
</template>

<script setup lang="ts">
import 'vue3-carousel/dist/carousel.css';
import { $computed } from 'vue/macros';
import { Carousel, Slide, Pagination, Navigation } from 'vue3-carousel';
import { resourceModule } from "@/store/modules";

const resource = $computed(() => resourceModule.getResource);
const digitalImages: Array<string> = $computed(() => resourceModule.getDigitalImages(resource));
</script>
