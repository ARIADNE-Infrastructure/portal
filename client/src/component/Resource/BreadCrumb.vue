<template>
  <div v-if="backtoPreviousItem" class="border-b-base border-gray">
    <div class="max-w-screen-xl py-sm px-base mx-auto w-full lg:flex">
      <router-link
        ref="link"
        :to="backtoPreviousItem.path"
        class=""
      >
        <i class="fas fa-arrow-left mr-sm"></i> <strong>Back to:</strong> {{ shortTitle(backtoPreviousItem.title) }}
      </router-link>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { $computed } from 'vue/macros';
import { useRoute } from 'vue-router'
import { resourceModule, breadCrumbModule } from "@/store/modules";
import utils from '@/utils/utils';

const route = useRoute();
const backtoPreviousItem = $computed(() => breadCrumbModule.getPrevious);
const crumbs = $computed(() => breadCrumbModule.getCrumbs);
const resource = $computed(() => resourceModule.getResource);

onMounted(() => {
  /**
   * Check if user comes from /resource route.
   * If not, destroy crumbtrail

    Crumbtrails is only for navigation between resources.
    If user lands on resource page coming from eg. /search or
    other page crumbs are destroyed.
  */
  if (!breadCrumbModule.getFrom?.startsWith("/resource")) {
    breadCrumbModule.resetCrumb();
  }
  breadCrumbModule.setCrumb({
    title: resourceModule.getMainTitle(resource),
    path: route.path,
  });
});

const shortTitle = (title: string): string => {
  return utils.trimString(title, 50);
}
</script>
