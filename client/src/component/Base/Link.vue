<template>
  <a
    v-if="href"
    ref="link"
    :href="href"
    :class="classes"
    :target="target"
  >
    <slot />
  </a>

  <a
    v-else-if="clickFn"
    href="#"
    :class="classes"
    v-on:click.prevent="clickFn"
  >
    <slot />
  </a>

  <router-link
    v-else-if="to"
    ref="link"
    :to="to"
    :class="classes"
  >
    <slot />
  </router-link>
</template>

<script lang="ts">
import { Component, Vue, Prop } from 'vue-property-decorator';

@Component
export default class BLink extends Vue {
  classes: string = '';

  @Prop() href?: string;
  @Prop() to?: string;
  @Prop() target?: string;
  @Prop() useDefaultStyle?: boolean;
  @Prop() breakWords?: boolean;
  @Prop() clickFn?: Function;

  mounted() {
    let link: any = this.$refs?.link;

    if (!link) {
      return;
    }

    if (this.to) {
      link = link.$el;
    }

    // apply default style if specified or if no other style has been set
    if (this.useDefaultStyle || !link.className) {
      this.classes = 'text-blue transition-colors duration-300 hover:text-darkGray hover:underline' +
        (this.breakWords ? ' break-word' : '');
    }
  }
}
</script>
