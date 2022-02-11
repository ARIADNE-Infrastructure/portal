<template>
  <span
    class="inline-flex relative group"
    :class="{ 'justify-center': isCenter }"
  >
    <span
      class="rounded-base absolute transition-opacity duration-300 text-md p-sm z-neg opacity-0 whitespace-no-wrap group-hover:opacity-100 group-hover:z-1005"
      :class="messageClasses"
      :style="`top: ${ top }; left: ${ left }; right: ${ right }`"
    >
      {{ title }}
      <slot name="content" />
    </span>
    <slot />
  </span>
</template>

<script lang="ts">
import { Component, Vue, Prop } from 'vue-property-decorator';

@Component
export default class HelpTooltip extends Vue {
  @Prop() title?: string;
  @Prop({ default: 'default' }) top?: string;
  @Prop({ default: 'default' }) left?: string;
  @Prop({ default: 'default' }) right?: string;
  @Prop({ default: true }) isCenter?: boolean;
  @Prop({ default: 'bg-darkGray-80 text-white' }) messageClasses?: string;
}
</script>
