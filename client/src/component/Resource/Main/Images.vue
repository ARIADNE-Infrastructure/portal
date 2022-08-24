<template>
  <div>
    <h3 class="text-lg font-bold mb-lg">
      <i class="far fa-image mr-sm"></i> Images
    </h3>

    <!-- slider -->
    <carousel
      :items-to-show="Math.min(digitalImages.length, amountToShow)"
      :wrapAround="digitalImages.length > amountToShow"
    >
      <slide v-for="(url, key) in digitalImages" :key="key">
        <div
          class="bg-contain bg-no-repeat bg-center cursor-pointer hover:opacity-80 duration-300"
          :style="`height:300px;transform:scale(0.95);background-image:url('${url}');width:${digitalImages.length < 2 && !utils.isMobile() ? '50%' : '100%'}`"
          @click.prevent="setActiveUrl(url)">
        </div>
      </slide>
      <template #addons v-if="digitalImages.length > amountToShow">
        <navigation />
        <pagination />
      </template>
    </carousel>

    <!-- popup -->
    <div
      class="fixed top-0 left-0 w-full h-full z-neg20 transition-all duration-500 bg-black-80 flex justify-center items-center cursor-pointer"
      :class="isActive ? 'z-40 opacity-1' : 'z-neg20 opacity-0'"
      @click="isActive = false"
    >
      <img v-if="activeUrl" :src="activeUrl" class="max-w-full max-h-full" />
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

const amountToShow: number = 3;
let activeUrl: string = $ref('');
let isActive: boolean = $ref(false);

const setActiveUrl = (url: string) => {
  activeUrl = url;
  isActive = true;
}
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
