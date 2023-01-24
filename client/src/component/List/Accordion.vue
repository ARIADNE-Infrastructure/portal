<!-- Front page -->
<template>
  <div class="border-base border-gray mb-md">
    <div
      class="p-md text-md bg-lightGray cursor-pointer select-none flex items-center justify-between"
      :class="{ 'transition-all duration-300 hover:bg-gray': hover }"
      @click="toggle"
    >
      <help-tooltip
        :enabled="!!description"
        top="-3.1rem"
        left="-0.85rem"
        messageClasses="bg-yellow text-white"
      >
        <div>
          <i
            class="fa-chevron-right fas mr-sm duration-200"
            :class="{ 'transform rotate-90': show }"
          />

          <template v-if="titleActive && titleInactive">
            <template v-if="show">{{ titleActive }}</template>
            <template v-else>{{ titleInactive }}</template>
          </template>

          <template v-else>{{ title }}</template>
        </div>

        <template v-slot:content>
          {{ description }}
        </template>
      </help-tooltip>
    </div>

    <transition
      v-on:before-enter="leave" v-on:enter="enter"
      v-on:before-leave="enter" v-on:leave="leave"
    >
      <!-- overflow-hidden -->
      <div
        v-if="show"
        :style="maxHeight && maxHeight < reactiveHeight ? ('max-height:' + maxHeight + 'px') : ''"
        :class="'ease-out duration-200 overflow-hidden' + (maxHeight && maxHeight < reactiveHeight ? ' overflow-y-scroll' : '')"
        :id="contentId"
      >
        <slot />
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { watch, onMounted, nextTick } from 'vue';
import { $ref, $computed, $$ } from 'vue/macros';
import utils from '@/utils/utils';
import HelpTooltip from '@/component/Help/Tooltip.vue';

const props = withDefaults(defineProps<{
  title?: string,
  description?: string,
  titleActive?: string,
  titleInactive?: string,
  initShow?: boolean,
  hover?: boolean,
  height?: number,
  maxHeight?: number,
  autoShow?: boolean,
}>(), {
  title: 'List',
  autoShow: false,
});

const contentId: string = 'content-' + utils.getUniqueId();
const reactiveHeight: number | undefined = $computed(() => props.height);
let show: boolean = $ref(false);

const toggle = (): void => {
  show = !show;
}

const enter = (el: HTMLElement): void => {
  el.style.height = el.scrollHeight + 'px';
}

const leave = (el: HTMLElement): void => {
  el.style.height = '0px';
}

onMounted(() => {
  nextTick(() => {
    show = !props.initShow;
    toggle();
  });
});

watch($$(reactiveHeight), () => {
  const el = document.getElementById(contentId);
  if (el?.style) {
    el.style.height = props.height + 'px';
  }
});

const reactiveAutoShow = $computed(() => props.autoShow);
watch($$(reactiveAutoShow), () => show = !!props.autoShow);
</script>
