<template>
  <div v-show="title === selectedTabTitle">
    <slot></slot>
  </div>
</template>

<script setup lang="ts">
import { inject } from 'vue';

const props = defineProps({
  icon: { type: String, required: true },
  title: { type: String, required: true },
});

// get selected tab title via depedency injection from parent, e.g. Tabs.vue
const selectedTabTitle: string = inject('selectedTabTitle');
</script>
