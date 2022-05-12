<template>
  <div>
    <div
      v-if="!forced && (isLoading || !resource)"
      class="py-3x px-base mx-auto max-w-screen-xl"
    >
      <h1 class="text-lg text-center text-midGray">
        Loading resource..
      </h1>
    </div>

    <div v-else class="text-mmd">
      <div v-if="utils.objectIsNotEmpty(params)">
        <b-link
          :to="utils.paramsToString('/search', params)"
          class="p-md bg-black-80 text-white border-b-base border-black hover:bg-blue transition-bg duration-300 w-full block text-center"
        >
          <i class="fas fa-long-arrow-alt-left mr-sm"></i>
          Back to search results
        </b-link>
      </div>

      <div>
        <resource-map v-if="resource" />
      </div>

      <!-- crumbtrails -->
      <div>
        <bread-crumb/>
      </div>

      <article class="py-xl px-base mx-auto max-w-screen-xl lg:flex">

        <div class="pt-xl w-full lg:w-2/3 lg:pr-2x">
          <resource-title class="mt-xs" />
          <resource-description class="mt-2x pt-xs mb-3x lg:mb-2x" />
          <resource-links class="mt-lg block lg:hidden" :resourceId="id" />
          <resource-main class="mt-lg" />
        </div>

        <div class="w-full lg:w-1/3 pt-xl lg:pl-2x lg:border-l-base border-gray">
          <resource-links class="hidden lg:block" :resourceId="id" />
          <resource-sidebar :initResource="initResource" />
        </div>
      </article>
    </div>
  </div>
</template>

<script setup lang="ts">
import { watch, onMounted, nextTick } from 'vue';
import { $ref, $computed } from 'vue/macros';
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router'
import { searchModule, generalModule, resourceModule } from "@/store/modules";
import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';

// unique
import ResourceMap from './Resource/Map.vue';
import ResourceTitle from './Resource/Title.vue';
import ResourceDescription from './Resource/Description.vue';
import ResourceLinks from './Resource/Links.vue';
import ResourceMain from './Resource/Main.vue';
import ResourceSidebar from './Resource/Sidebar.vue';
import BreadCrumb from './Resource/BreadCrumb.vue';

const props = defineProps({
  id: String,
});

const router = useRouter();
const route = useRoute();
let forced: boolean = $ref(false);

const isLoading: boolean = $computed(() => generalModule.getIsLoading);
const resource = $computed(() => resourceModule.getResource);
const params = $computed(() => searchModule.getParams);

onMounted(() => {
  initResource(props.id);
});

const initResource = async (id: any, force: boolean = false) => {
  id = String(id);
  forced = force;

  if (resource?.id && resource.id === id && !force) {
    return;
  }

  await resourceModule.setResource(id);

  nextTick(() => {
    if (resource) {
      // Set document metadata
      generalModule.setMeta({
        title: (resource.title?.text && resource.title?.text.trim()) ? resource.title?.text : 'No title',
        description: resource?.description?.text ? resource?.description?.text.slice(0, 155) : ''
      });
    } else {
      router.replace('/404');
    }
  });
}

const unwatch = watch(route, async (to: any) => {
  if (to?.params?.id) {
    await initResource(to.params.id);
  }
});

onBeforeRouteLeave(unwatch);
</script>
