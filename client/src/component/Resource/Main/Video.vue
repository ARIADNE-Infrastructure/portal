<template>
  <div v-if="videoUrls.length">
    <h3 class="text-lg font-bold mb-lg">
      <i class="fas fa-video mr-sm"></i> Video
    </h3>

    <ul class="mt-md w-full">
      <li v-for="(url, key) in videoUrls" class="mb-xs" :key="key">
        <i class="fas fa-video mr-sm"></i>
        <b-link :href="url.url" class="text-blue transition-colors duration-300 hover:text-darkGray hover:underline" @click.prevent="openVideo(url)">
          {{ url.name }}
        </b-link>
      </li>
    </ul>

    <div class="fixed top-0 left-0 w-full h-full bg-black-80 justify-center items-center cursor-pointer flex-col z-20" :class="activeVideo ? 'flex' : 'hidden'" id="video-popup" @click="closeVideo">
      <p class="text-white text-lg mb-sm p-sm cursor-pointer break-word" id="video-title">
        {{ activeVideo ? activeVideo.name : '' }}
      </p>
      <p v-if="isError" class="text-white bg-red-60 text-lg mb-lg p-sm cursor-pointer" id="video-error">
        Error: Video file could not be loaded.
      </p>
      <video ref="videoRef" muted controls autoplay class="cursor-default"></video>
    </div>
  </div>
</template>

<script setup lang="ts">
import { $computed, $ref } from 'vue/macros';
import { onBeforeRouteLeave } from 'vue-router';
import { resourceModule } from '@/store/modules';
import BLink from '@/component/Base/Link.vue';

const resource = $computed(() => resourceModule.getResource);
const videoUrls = $computed(() => resourceModule.getVideoUrls(resource));
let videoRef = $ref(null);
let activeVideo = $ref(null);
let isError = $ref(false);

const openVideo = (url: any) => {
  videoRef.onerror = () => isError = !!activeVideo;
  activeVideo = url;
  if (videoRef.src !== url.url || isError) {
    videoRef.src = url.url;
  }
  isError = false;
}

const closeVideo = (event: any) => {
  if (/^video\-(popup|title|error)$/.test(event.target?.id)) {
    videoRef.pause();
    videoRef.onerror = null;
    activeVideo = null;
  }
}

onBeforeRouteLeave(() => {
  if (videoRef?.onerror) {
    videoRef.onerror = null;
  }
})
</script>
