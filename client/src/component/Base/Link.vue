<template>
  <a
    v-if="href"
    :id="linkId"
    :href="href"
    :class="cssClasses"
    :target="target"
  >
    <slot />
  </a>

  <a
    v-else-if="clickFn"
    href="#"
    :class="cssClasses"
    v-on:click.prevent="clickFn"
  >
    <slot />
  </a>

  <router-link
    v-else-if="to"
    :id="linkId"
    :to="to"
    :class="cssClasses"
  >
    <slot />
  </router-link>
</template>

<script setup lang="ts">
import utils from '@/utils/utils';
import { onMounted } from 'vue';
import { $ref } from 'vue/macros';

const props = defineProps({
  href: String,
  to: String,
  target: String,
  useDefaultStyle: Boolean,
  breakWords: Boolean,
  clickFn: Function,
});

let cssClasses = $ref('');
const linkId = 'b-link-' + utils.getUniqueId();

// apply default style if specified or if no other style has been set
onMounted(() => {
  const link = document.getElementById(linkId);

  if (link && (props.useDefaultStyle || !link.className)) {
    cssClasses = 'text-blue transition-colors duration-300 hover:text-darkGray hover:underline' + (props.breakWords ? ' break-word' : '');
  }
});
</script>
