<template>
  <div v-if="isValidating || validDigitalImages.length">
    <h3 class="text-lg font-bold mb-lg">
      <i class="far fa-image mr-sm"></i> Images
    </h3>

    <!-- loading -->
    <div v-if="isValidating">
      Loading..
    </div>
    <div v-else>
      <!-- slider -->
      <carousel
        :items-to-show="Math.min(validDigitalImages.length, amountToShow)"
        :wrapAround="validDigitalImages.length > amountToShow"
      >
        <slide v-for="(url, key) in validDigitalImages" :key="key">
          <div class="cursor-pointer hover:opacity-80 duration-300 px-sm pb-md" @click.prevent="activeUrl = url">
            <img :src="url" class="max-w-full">
          </div>
        </slide>
        <template #addons v-if="validDigitalImages.length > amountToShow">
          <navigation />
          <pagination />
        </template>
      </carousel>

      <!-- popup -->
      <div
        class="fixed top-0 left-0 w-full h-full bg-black-80 justify-center items-center cursor-pointer z-20"
        :class="activeUrl ? 'flex' : 'hidden'"
        @click="activeUrl = ''"
      >
        <img v-if="activeUrl" :src="activeUrl" class="max-w-full max-h-full" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import 'vue3-carousel/dist/carousel.css';
import { $computed, $ref } from 'vue/macros';
import { Carousel, Slide, Pagination, Navigation } from 'vue3-carousel';
import { resourceModule } from "@/store/modules";
import utils from '@/utils/utils';

const resource = $computed(() => resourceModule.getResource);
const digitalImages: Array<string> = $computed(() => resourceModule.getDigitalImages(resource));

const amountToShow: number = utils.isMobile() ? 2 : 3;
let validDigitalImages: Array<string> = $ref([]);
let isValidating: boolean = $ref(true);
let activeUrl: string = $ref('');

// validates all images before use
let count = 0;
digitalImages.forEach((url: string, index: number) => {
  const img = new Image();
  const next = () => {
    img.onload = null;
    img.onerror = null;
    if (++count >= digitalImages.length) {
      validDigitalImages = validDigitalImages.filter((url: string) => url);
      isValidating = false;
    }
  }
  img.onload = () => {
    validDigitalImages[index] = url;
    next();
  }
  img.onerror = next;
  img.src = url;
});

</script>

<style>
.carousel__next, .carousel__prev{
  background: #D5A03A;
  transition: opacity 0.3s;
}
.carousel__next:hover, .carousel__prev:hover{
  opacity: 0.8;
}
</style>
