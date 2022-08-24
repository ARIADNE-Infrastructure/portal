<template>
  <div>
    <ul class="flex items-center">
      <li
        v-for="(tab, index) in tabs"
        :key="tab.props.title"
        class="text-white -mb-1 text-md p-md rounded-t-base transition-all duration-300 focus:outline-none cursor-pointer flex-1"
        :class="{
          'bg-blue': tab.props.title === selectedTabTitle,
          'bg-blue-50 hover:bg-blue': tab.props.title !== selectedTabTitle,
          'ml-sm': index,
        }"
        @click="selectTab(tab.props.title)"
      >
        <i
          class="mr-xs"
          :class="tab.props.icon"
        />

        {{ tab.props.title }}
      </li>
    </ul>

    <div class="border-base rounded-t-0 border-gray rounded-base px-md py-base">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { $ref, computed, provide, getCurrentInstance, onMounted, nextTick } from 'vue';

const props = defineProps<{
  initiallySelectedTabTitle: string,
}>();

let selectedTabTitle: string = $ref('');
const tabs = getCurrentInstance().slots.default();

// provide selected tab title via depedency injection to children/slots, i.e. Tab.vue
provide('selectedTabTitle', computed(() => selectedTabTitle));

// select initial tab on load
onMounted(() => nextTick(() => selectTab(props.initiallySelectedTabTitle)));

// select tab by title
const selectTab = (title: string): void => {
  selectedTabTitle = title
};
</script>
