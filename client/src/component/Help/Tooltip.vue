<template>
  <span
    class="inline-flex relative group"
    :class="{ 'justify-center': isCenter }"
  >
    <span
      v-show="enabled"
      class="rounded-base absolute transition-opacity duration-300 text-md p-sm z-neg10 opacity-0 whitespace-no-wrap group-hover:opacity-100 group-hover:z-20"
      :class="messageClasses"
      :style="`top: ${ top }; left: ${ left }; right: ${ right }`"
    >
      {{ title }}
      <slot name="content" />
    </span>
    <slot />
  </span>
</template>

<script setup lang="ts">
defineProps({
  title: String,
  top: { type: String, default: 'default' },
  left: { type: String, default: 'default' },
  right: { type: String, default: 'default' },
  isCenter: { type: Boolean, default: true },
  messageClasses: { type: String, default: 'bg-darkGray-80 text-white' },
  enabled: { type: Boolean, default: true },
});
</script>
