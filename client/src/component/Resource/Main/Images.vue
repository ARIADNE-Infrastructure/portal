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
            <img :src="url" class="max-w-full" style="max-height: 350px">
          </div>
        </slide>
        <template #addons>
          <div v-if="validDigitalImages.length > amountToShow">
            <navigation />
            <pagination />
          </div>
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
import { watch } from 'vue';
import { $computed, $ref, $$ } from 'vue/macros';
import { onBeforeRouteLeave } from 'vue-router'
import { Carousel, Slide, Pagination, Navigation } from 'vue3-carousel';
import { generalModule, resourceModule } from "@/store/modules";
import utils from '@/utils/utils';

const emit = defineEmits(['error']);
const window = $computed(() => generalModule.getWindow);
const resource = $computed(() => resourceModule.getResource);
const digitalImages: Array<string> = $computed(() => resourceModule.getDigitalImages(resource));
let amountToShow: number = $ref(utils.isMobile() ? 1 : 3);
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
      emit('error', !validDigitalImages.length);
    }
  }
  img.onload = () => {
    validDigitalImages[index] = url;
    next();
  }
  img.onerror = next;
  img.src = url;
});

onBeforeRouteLeave(watch($$(window), () => amountToShow = utils.isMobile() ? 1 : 3));
</script>

<style>
.carousel__next, .carousel__prev{
  background: #D5A03A;
  transition: opacity 0.3s;
}
.carousel__next:hover, .carousel__prev:hover{
  opacity: 0.8;
}
.carousel__slide{
  height: inherit !important;
}
</style>
