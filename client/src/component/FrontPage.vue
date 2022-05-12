<!-- Front page -->
<template>
  <div class="pt-3x max-w-screen-lg mx-auto">
    <div class="px-2x pb-4x pt-4x lg:pt-8x 2xl:pt-11x">
      <div class="flex items-center justify-center">
        <img :src="`${ assets }/frontpage/big-logo.png`"
          alt="ariadne logo"
          class="mb-xl"
        />
        <span class="text-green hidden md:block"
          style="font-size: 36px; text-shadow: 1px 1px 2px black; font-family: 'PT Sans';">
          plus
        </span>
      </div>

      <filter-search
        class="mt-2x max-w-2xl m-auto"
        color="yellow"
        hoverStyle="hover:bg-yellow-80"
        focusStyle="focus:border-yellow"
        :veryBig="true"
        :autoFocus="true"
        :showFields="fieldsType"
        :useCurrentSearch="false"
        :hasAutocomplete="true"
      />
    </div>

    <div class="fixed top-0 left-0 w-screen h-screen z-neg10" style="background:linear-gradient(rgba(0,0,0,.5),rgba(0,0,0,1))"></div>
    <div
      class="fixed top-0 left-0 w-screen h-screen z-neg20 bg-cover bg-no-repeat bg-center"
      :style="bgImage"></div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { $ref, $computed } from 'vue/macros';
import { searchModule, generalModule } from "@/store/modules";
import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';
import HelpTooltip from '@/component/Help/Tooltip.vue';
import FilterSearch from '@/component/Filter/Search.vue';

let bg: number = $ref(0);

const window = $computed(() => generalModule.getWindow);
const assets: string = $computed(() => generalModule.getAssetsDir);
const fieldsType: string = $computed(() => window.innerWidth < 568 ? 'select' : 'radio');
const bgImage = $computed(() => ({
  'background-image': `url(${ assets }/frontpage/bg-${ bg }.jpg)`
}));

onMounted(() => {
  bg = Math.floor(Math.random() * 19) + 1;

  // Reset main sore states, if any.
  searchModule.actionResetResultState();

  const input: HTMLInputElement | null = document.querySelector('.auto-focus');
  if (input && !utils.isMobile()) {
    input.focus();
  }
});
</script>
