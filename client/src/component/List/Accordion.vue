<!-- Front page -->
<template>
  <div class="border-base border-gray rounded-base mb-md">
    <div
      class="rounded-base p-md text-md bg-lightGray cursor-pointer select-none flex items-center justify-between"
      :class="{ 'rounded-b-0': show, 'transition-all duration-300 hover:bg-gray': hover }"
      @click="toggle"
    >
      <div>
        <i
          class="fa-chevron-right fas mr-xs duration-200"
          :class="{ 'transform rotate-90': show }"
        />
        {{ title }}
      </div>
    </div>

    <transition
      v-on:before-enter="leave" v-on:enter="enter"
      v-on:before-leave="enter" v-on:leave="leave"
    >
      <div
        v-if="show"
        class="ease-out duration-200 overflow-hidden"
        ref="content"
      >
        <slot />
      </div>
    </transition>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Mixins, Prop, Watch } from 'vue-property-decorator';
import { general } from "@/store/modules";
import ListAccordionMixin from './AccordionMixin.vue';

@Component
export default class ListAccordion extends Mixins(ListAccordionMixin) {
  @Prop() title!: string;
  @Prop() initShow!: boolean;
  @Prop() hover?: boolean;
  @Prop() height?: number;
  @Prop({ default: false}) autoShow?: boolean;

  mounted() {
    this.show = !this.initShow;
    this.toggle();
  }

  @Watch('height')
  onHeightUpdate() {
    const el = this.$refs?.content as HTMLDivElement;
    if (el?.style) {
      el.style.height = this.height + 'px';
    }
  }

  @Watch('autoShow')
  onAutoShowUpdate() {
    this.show = <boolean>this.autoShow;
  }
}
</script>
