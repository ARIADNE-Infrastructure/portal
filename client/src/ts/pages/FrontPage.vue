<!-- Front page -->
<template>
	<div class="max-w-screen-lg mx-auto">
    <div class="bg-white-80 py-4x px-2x">
      <img
        :src="`${ assets }/frontpage/big-logo.png`"
        alt="ariadne logo"
        class="m-auto mb-xl"
      >

      <search
        class="max-w-2xl m-auto"
        color="yellow"
        bg="bg-yellow"
        hover="hover:bg-yellow-80"
        focus="focus:border-yellow"
        :big="true"
        :autoFocus="true"
        :showFields="true"
        :useCurrentSearch="false"
        :hasAutocomplete="true"
      />
    </div>

    <div class="bg-white-80 py-2x px-2x mt-2x">
      <h1 class="text-2x border-b-base border-red mb-md pb-xs">
        Welcome
      </h1>
      <p>Explore the digital resources and services that ARIADNE has brought together from across Europe for archaeological research, learning and teaching.</p>
    </div>

    <div class="bg-white-80 pt-2x md:pb-2x px-2x mt-2x mb-6x">
      <h2 class="text-2xl border-b-base border-red mb-md pb-sm">
        Browse the Catalog
      </h2>

      <div class="flex flex-col md:flex-row pt-xs">
        <router-link v-for="(link, key) in links" :key="key"
          :to="`/browse/${ link.id }`"
          class="w-full lg:w-1/3 relative hover:opacity-80 transition-opacity duration-300 border-base border-white mb-2x md:mb-none"
          :class="{ 'md:mr-2x': !key, 'md:ml-2x': key === links.length - 1 }">
          <span class="absolute top-sm left-sm bg-white px-md py-sm border-base border-blue transition-all duration-300 hover:bg-blue hover:text-white">
            <i :class="link.icon"></i>
            {{ utils.sentenceCase(link.id) }}
          </span>
          <img :src="`${ assets }/frontpage/${ link.id }.png`" :alt="link.id" class="w-full">
        </router-link>
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
import Search from '../components/Form/Search.vue';
import utils from '../utils/utils';

@Component({
  components: {
    Search
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
    return this.$store.getters.frontPageLinks();
  }

  get assets() {
    return this.$store.getters.assets();
  }

  get bgImage() {
    return {
      'background-image': `url(${ this.assets }/frontpage/bg-${ this.bg }.jpg)`
    };
  }
}
</script>
