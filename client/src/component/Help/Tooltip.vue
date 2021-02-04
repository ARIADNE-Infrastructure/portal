<template>
  <span class="inline-flex relative justify-center group">
    <span class="absolute transition-opacity duration-300 bg-darkGray-80 text-white text-md p-sm z-neg opacity-0 whitespace-no-wrap group-hover:opacity-100 group-hover:z-1005"
      :style="`top:calc(-100% - ${ top })`">
      {{ title }}
    </span>
    <slot />
  </span>
</template>

<script lang="ts">
import { Component, Vue, Prop } from 'vue-property-decorator';

@Component
export default class HelpTooltip extends Vue {
  @Prop() title!: string;
  @Prop() top!: string;
}
</script>
