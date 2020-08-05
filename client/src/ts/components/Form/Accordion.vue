<!-- Front page -->
<template>
  <div class="mb-lg border-base border-gray rounded-base">
    <div
      class="rounded-base bg-lightGray p-md cursor-pointer select-none"
      :class="{ 'rounded-b-0': show, 'transition-all duration-300 hover:bg-gray': hover }"
      @click="toggle"
    >
      <i
        class="fa-chevron-right fas mr-xs duration-200"
        :class="{ 'transform rotate-90': show }"
      />
      {{ title }}
    </div>

    <transition
      v-on:before-enter="leave" v-on:enter="enter"
      v-on:before-leave="enter" v-on:leave="leave"
    >
      <div
        v-if="!loading && show"
        class="ease-out duration-200 overflow-hidden"
      >
        <slot />
      </div>
    </transition>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Mixins, Prop } from 'vue-property-decorator';
import AccordionMixin from './AccordionMixin.vue';

@Component
export default class Accordion extends Mixins(AccordionMixin) {
  @Prop() title!: string;
  @Prop() initShow!: boolean;
  @Prop() hover?: boolean;

  get loading () {
    return this.$store.getters.loading();
  }

  mounted() {
    this.show = !this.initShow;
    this.toggle();
  }
}
</script>
