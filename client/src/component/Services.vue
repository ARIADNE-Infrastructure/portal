<!-- Front page -->
<template>
	<div class="pt-3x px-base max-w-screen-xl mx-auto">
    <h1 class="text-2xl mb-lg">ARIADNE's services</h1>

    <form @submit.prevent="utils.blurMobile()">
      <input type="text" placeholder="Search among ARIADNE services.."
        class="border-base border-gray w-full max-w-3xl p-sm focus:border-yellow outline-none rounded-base"
        ref="serviceInput"
        v-model="filter">
    </form>

    <h2 v-if="!Object.values(filtered).some(a => a.length)" class="mt-3x text-lg">
      No services matching "{{ filter.trim() }}"
    </h2>
    <div v-else class="mt-3x">
      <div v-for="(items, title) in filtered" :key="title">
        <div v-if="items && items.length" class="border-t-base border-gray pt-xl pb-2x">
          <h2 class="text-lg mb-xl">{{ title }}</h2>

          <b-link
            v-for="(item, key) in items"
            :key="key"
            :href="item.url"
            target="_blank"
            class="flex flex-col max-w-full lg:flex-row lg:items-start cursor-pointer group mb-2x pb-xl border-b-base border-gray lg:border-b-0"
          >
            <img :src="`${ assets }/services/${ item.img }`" :alt="item.img" width="360"
              class="flex-shrink-0 backface-hidden transition-opacity duration-300 border-base border-gray p-sm rounded-base group-hover:opacity-80">
            <div class="mt-md lg:ml-xl lg:mt-none text-mmd">
              <h3 class="text-lg group-hover:underline" v-html="utils.getMarked(item.title, filter)"></h3>
              <p class="mt-md mb-lg whitespace-pre-line" v-html="utils.getMarked(item.description, filter)"></p>
              <p>
                <i class="fas fa-external-link-alt mr-xs text-blue"></i>
                <span class="break-word text-blue transition-colors duration-300 hover:text-darkGray hover:underline" v-if="item.url" v-html="utils.getMarked(item.url, filter)"></span>
              </p>
            </div>
          </b-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import { general } from "@/store/modules";
import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';

@Component({
  components: {
    BLink,
  }
})
export default class Services extends Vue {
  filter = '';
  utils = utils;

  mounted () {
    if (!utils.isMobile()) {
      (this.$refs.serviceInput as any).focus();
    }
  }

  get assets(): string {
    return general.getAssetsDir;
  }

  get filtered () {
    let filter = this.filter.toLowerCase().trim(),
        ret = {};

    for (let key in general.getServices) {
      ret[key] = general.getServices[key].filter((s: any) => {
        return s.title.toLowerCase().includes(filter) ||
          s.description.toLowerCase().includes(filter) ||
          s.url.toLowerCase().includes(filter);
      });
    }
    return ret;
  }
}
</script>
