<!-- Front page -->
<template>
	<div>
    <h1 class="text-2x mb-lg">ARIADNE's services</h1>

    <form v-on:submit.prevent="utils.blurMobile()">
      <input type="text" placeholder="Search among ARIADNE services.."
        class="border-base border-gray w-full max-w-3xl p-sm focus:border-yellow outline-none rounded-base"
        ref="serviceInput"
        v-model="filter">
    </form>

    <h2 v-if="!Object.values(filtered).some(a => a.length)" class="my-3x text-xl">
      No services matching "{{ filter.trim() }}"
    </h2>
    <div v-else class="my-3x">
      <div v-for="(items, title) in filtered" :key="title">
        <div v-if="items && items.length" class="border-t-base border-gray pt-xl pb-2x">
          <h2 class="text-xl mb-xl">{{ title }}</h2>

          <a v-for="(item, key) in items" :key="key"
            :href="item.url" target="_blank"
            class="flex flex-col max-w-full lg:flex-row lg:items-start cursor-pointer group mb-2x pb-xl border-b-base border-gray lg:border-b-0">
            <img :src="`${ assets }/services/${ item.img }`" :alt="item.img" width="360"
              class="flex-shrink-0 backface-hidden transition-opacity duration-300 border-base border-gray p-sm rounded-base group-hover:opacity-80">
            <div class="mt-md lg:ml-xl lg:mt-none">
              <h3 class="text-lg group-hover:underline" v-html="utils.getMarked(item.title, filter)"></h3>
              <p class="my-md" v-html="utils.getMarked(item.description, filter)"></p>
              <p>
                <i class="fas fa-external-link-alt mr-xs text-blue"></i>
                <span :class="utils.linkClasses()" class="break-word" v-html="utils.getMarked(item.url, filter)"></span>
              </p>
            </div>
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import utils from '../utils/utils';

@Component
export default class Services extends Vue {
  filter = '';
  utils = utils;

  mounted () {
    if (!utils.isMobile()) {
      (this.$refs.serviceInput as any).focus();
    }
  }

  get services () {
    return this.$store.getters.services();
  }
  get assets () {
    return this.$store.getters.assets();
  }

  get filtered () {
    let filter = this.filter.toLowerCase().trim(),
        ret = {};

    for (let key in this.services) {
      ret[key] = this.services[key].filter((s: any) => {
        return s.title.toLowerCase().includes(filter) ||
          s.description.toLowerCase().includes(filter) ||
          s.url.toLowerCase().includes(filter);
      });
    }
    return ret;
  }
}
</script>
