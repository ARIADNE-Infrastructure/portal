<template>
  <div v-if="audioUrls.length">
    <h3 class="text-lg font-bold mb-lg">
      <i class="fas fa-music mr-sm"></i> Audio
    </h3>

    <ul class="mt-md w-full">
      <li v-for="(url, key) in audioUrls" class="mb-xs" :key="key">
        <i class="fas fa-volume-down mr-sm"></i>
        <b-link :href="url.url" class="text-blue transition-colors duration-300 hover:text-darkGray hover:underline" @click.prevent="openAudio(url)">
          {{ url.name }}
        </b-link>
      </li>
    </ul>

    <div class="fixed top-0 left-0 w-full h-full bg-black-80 justify-center items-center cursor-pointer flex-col z-20" :class="activeAudio ? 'flex' : 'hidden'" id="audio-popup" @click="closeAudio">
      <p class="text-white text-lg mb-sm p-sm cursor-pointer break-word" id="audio-title">
        {{ activeAudio ? activeAudio.name : '' }}
      </p>
      <p v-if="isError" class="text-white bg-red-60 text-lg mb-lg p-sm cursor-pointer" id="audio-error">
        Error: Audio file could not be loaded.
      </p>
      <audio ref="audioRef" controls class="cursor-default"></audio>
    </div>
  </div>
</template>

<script setup lang="ts">
import { $computed, $ref } from 'vue/macros';
import { onBeforeRouteLeave } from 'vue-router';
import { resourceModule } from "@/store/modules";
import BLink from '@/component/Base/Link.vue';

const resource = $computed(() => resourceModule.getResource);
const audioUrls = $computed(() => resourceModule.getAudioUrls(resource));
let audioRef = $ref(null);
let activeAudio = $ref(null);
let isError = $ref(false);

const openAudio = (url: any) => {
  audioRef.pause();
  audioRef.onerror = () => isError = !!activeAudio;
  activeAudio = url;
  if (audioRef.src !== url.url || isError) {
    audioRef.src = url.url;
  }
  isError = false;
}

const closeAudio = (event: any) => {
  if (/^audio\-(popup|title|error)$/.test(event.target?.id)) {
    audioRef.pause();
    audioRef.onerror = null;
    activeAudio = null;
  }
}

onBeforeRouteLeave(() => {
  if (audioRef?.onerror) {
    audioRef.onerror = null;
  }
})
</script>
