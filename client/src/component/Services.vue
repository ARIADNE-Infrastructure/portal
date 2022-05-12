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

          <div v-for="(item, key) in items" :key="key"
            class="flex flex-col max-w-full lg:flex-row lg:items-start mb-2x pb-xl border-b-base border-gray lg:border-b-0"
          >
            <!-- img -->
            <b-link :href="item.url" target="_blank" class="flex-shrink-0">
              <img :src="`${ assets }/services/${ item.img }`" :alt="item.img" width="360"
                class="backface-hidden transition-opacity duration-300 border-base border-gray p-sm hover:opacity-80">
            </b-link>

            <div class="mt-md lg:ml-xl lg:mt-none text-mmd">
              <!-- title -->
              <b-link :href="item.url" target="_blank" class="flex-shrink-0 hover:underline">
                <h3 class="text-lg" v-html="item.title"></h3>
              </b-link>

              <!-- text -->
              <p class="mt-md mb-lg whitespace-pre-line services-a-style" v-html="item.description"></p>

              <!-- link -->
              <p v-if="item.url">
                <b-link :href="item.url" target="_blank" class="break-word text-blue hover:underline">
                  <i class="fas fa-external-link-alt mr-sm"></i>
                  <span v-html="item.url"></span>
                </b-link>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { $ref, $computed } from 'vue/macros';
import { generalModule } from "@/store/modules";
import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';

let filter: string = $ref('');
let serviceInput: any = $ref(null);

const assets: string = $computed(() => generalModule.getAssetsDir);

const filtered = $computed(() => {
  const str = filter.toLowerCase().trim();
  const ret: any = {};

  for (let key in generalModule.getServices) {
    ret[key] = generalModule.getServices[key].filter((s: any) => {
      return s.title.toLowerCase().includes(str) ||
        s.description.toLowerCase().includes(str) ||
        s.url.toLowerCase().includes(str);
    });
  }
  return ret;
});

onMounted(() => {
  if (!utils.isMobile()) {
    serviceInput.focus();
  }
});
</script>
