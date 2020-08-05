<template>
  <span class="inline-flex relative justify-center tooltip-trigger">
    <span class="absolute transition-all duration-300 bg-darkGray-80 text-white p-sm z-neg opacity-0 whitespace-no-wrap tooltip-target"
      :style="`top:calc(-100% - ${ top })`">
      {{ title }}
    </span>
    <slot />
  </span>
</template>

<script lang="ts">
import { Component, Vue, Prop } from 'vue-property-decorator';

@Component
export default class Tooltip extends Vue {
  @Prop() title!: string;
  @Prop() top!: string;
}
</script>

<style>
.tooltip-trigger:hover .tooltip-target {
  opacity: 1;
  z-index: 2000;
}
</style>
