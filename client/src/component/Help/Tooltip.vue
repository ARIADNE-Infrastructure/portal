<template>
  <span
    class="inline-flex relative group"
    :class="{ 'justify-center': isCenter }"
  >
    <span
      v-show="enabled"
      class="absolute transition-opacity duration-300 text-md p-sm z-neg10 opacity-0 whitespace-no-wrap group-hover:opacity-100 group-hover:z-20"
      :class="messageClasses"
      :style="`top:${ top };left:${ left };right:${ right };${ customStyle }`"
    >
      {{ title }}
      <slot name="content" />
    </span>
    <slot />
  </span>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
  title?: string,
  top?: string,
  left?: string,
  right?: string,
  customStyle?: string,
  isCenter?: boolean,
  messageClasses?: string,
  enabled?: boolean,
}>(), {
  top: 'default',
  left: 'default',
  right: 'default',
  customStyle: '',
  isCenter: true,
  messageClasses: 'bg-darkGray-80 text-white',
  enabled: true,
});
</script>
