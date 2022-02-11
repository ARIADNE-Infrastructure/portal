<!-- Front page -->
<template>
	<div class="pt-3x max-w-screen-lg mx-auto">
    <div class="bg-white-80 py-4x px-2x">
      <img
        :src="`${ assets }/frontpage/big-logo.png`"
        alt="ariadne logo"
        class="m-auto mb-xl"
      >

      <filter-search
        class="mt-2x max-w-2xl m-auto"
        color="yellow"
        hoverStyle="hover:bg-yellow-80"
        focusStyle="focus:border-yellow"
        :big="true"
        :autoFocus="true"
        showFields="radio"
        :useCurrentSearch="false"
        :hasAutocomplete="true"
      />
    </div>

    <div class="bg-white-80 py-2x px-2x mt-2x">
      <h1 class="text-2xl border-b-base border-red mb-md pb-xs">
        Welcome
      </h1>
      <p class="text-mmd">
        Explore the digital resources and services that ARIADNE has brought together from across the World for archaeological research, learning and teaching.
      </p>
    </div>

    <div class="bg-white-80 pt-2x md:pb-2x px-2x mt-2x mb-base">
      <div class="flex items-center border-b-base border-red mb-md pb-sm">
        <h2 class="text-xl">
          Browse the Catalogue
        </h2>

        <help-tooltip
          title="Click to view ARIADNE portal guide"
          top="-.35rem"
          left="2rem"
        >
          <b-link
            href="guide"
            class="mt-1 ml-sm"
          >
            <i class='fas fa-question-circle text-base'/>
          </b-link>
        </help-tooltip>
      </div>

      <div class="flex flex-col md:flex-row pt-xs text-mmd">
        <b-link
          v-for="(link, key) in links"
          :key="key"
          :to="`/browse/${ link.id }`"
          class="w-full lg:w-1/3 relative mb-2x md:mb-none group"
          :class="{ 'md:mr-2x': !key, 'md:ml-2x': key === links.length - 1 }"
        >
          <span class="absolute z-1 top-sm left-sm bg-white px-md py-sm border-base border-blue transition-all duration-300 group-hover:bg-blue group-hover:text-white">
            <i class="mr-xs" :class="link.icon"></i>
            {{ utils.sentenceCase(link.id) }}
          </span>
          <img :src="`${ assets }/frontpage/${ link.id }.png`" :alt="link.id" class="w-full transition-opacity duration-300 group-hover:opacity-80 backface-hidden">
        </b-link>
      </div>
    </div>

    <div
      class="fixed top-0 left-0 w-screen h-screen z-neg bg-cover bg-no-repeat bg-center"
      :style="bgImage"
    >
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import { generalModule } from "@/store/modules";
import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';
import HelpTooltip from '@/component/Help/Tooltip.vue';

import FilterSearch from '@/component/Filter/Search.vue';

@Component({
  components: {
    BLink,
    HelpTooltip,
    FilterSearch
  }
})
export default class FrontPage extends Vue {
  utils = utils;
  bg: number = 0;

  created() {
    this.bg = Math.floor(Math.random() * 15) + 1;
  }

  mounted() {
    let input: any = document.querySelector('.auto-focus');
    if (input && !utils.isMobile()) {
      input.focus();
    }
  }

  get links() {
    return generalModule.getFrontPageLinks;
  }

  get assets(): string {
    return generalModule.getAssetsDir;
  }

  get bgImage() {
    return {
      'background-image': `url(${ this.assets }/frontpage/bg-${ this.bg }.jpg)`
    };
  }
}
</script>
