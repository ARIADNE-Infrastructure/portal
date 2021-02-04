<template>
  <resource-filtered-items :items="resource.distribution">
    <div slot-scope="{ item }">
      <h3 class="text-lg font-bold mb-lg">
        <i class="fas fa-network-wired mr-xs"></i>
        Distribution
      </h3>

      <div v-if="item.title" :class="itemClass">
        <b :class="bClass">Title:</b>
        {{ item.title }}
      </div>

      <div v-if="item.description" :class="itemClass">
        <b :class="bClass">Description:</b>
        <div class="mt-sm whitespace-pre-line">{{ utils.cleanText(item.description, true) }}</div>
      </div>

      <div v-if="utils.validUrl(item.accessURL)" :class="itemClass">
        <b :class="bClass">Access URL:</b>
        <b-link
          :href="item.accessURL"
          target="_blank"
          class="break-word"
          :useDefaultStyle="true"
        >
          {{ item.accessURL }}
        </b-link>
      </div>

      <div v-if="item.issued" :class="itemClass">
        <b :class="bClass">Issued:</b>
        {{ utils.formatDate(item.issued) }}
      </div>

      <div v-if="item.modified" :class="itemClass">
        <b :class="bClass">Modified:</b>
        {{ utils.formatDate(item.modified) }}
      </div>
    </div>
  </resource-filtered-items> 
</template>

<script lang="ts">
import { Component, Vue, Prop } from 'vue-property-decorator';
import { resource } from "@/store/modules";
import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';

import ResourceFilteredItems from '../FilteredItems.vue';

@Component({
  components: {
    BLink,
    ResourceFilteredItems,
  }
})
export default class ResourceMainDistribution extends Vue {
  utils = utils;

  @Prop() itemClass!: string;
  @Prop() bClass!: string;

  get resource(): any {
    return resource.getResource;
  }
}
</script>