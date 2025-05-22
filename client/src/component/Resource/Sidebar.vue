<template>
  <div>
    <section
      v-if="collection?.hits?.length && collection.total"
      :class="sectionClass"
    >
      <h3 class="text-lg font-bold mb-lg">
        <i class="fas fa-copy mr-sm"></i>
        Resource has {{ collection.total }} records
      </h3>

      <resource-filtered-items
        :items="getTitleSorted(collection.hits)"
        slotType="resource"
        icon="fas mr-sm fa-database"
      />

      <b-link
        v-if="collection.total"
        :to="utils.paramsToString('/search', { isPartOf: resource.identifier, isPartOfLabel: resource.title ? resource.title.text : '' })"
        class="mb-sm block"
        :useDefaultStyle="true"
      >
        <i class="fas fa-search mr-sm mt-md"></i>
        Show all records
      </b-link>
    </section>

    <section
      v-if="resource.isAboutResource && resource.isAboutResource.length"
      :class="sectionClass"
    >
      <h3 class="text-lg font-bold mb-lg">
        <i class="fas fa-copy mr-sm" />
        Resource is about
      </h3>

      <resource-filtered-items :items="resource.isAboutResource">
        <template v-slot="{ item }">
          <b-link :href="item.id" target="_blank" >
            {{ item.title.text }}
          </b-link>
        </template>
      </resource-filtered-items>
    </section>

    <section
      v-if="resource.partOf?.length"
      :class="sectionClass"
    >
      <h3 class="text-lg font-bold mb-lg">
        <i class="fas fa-copy mr-sm"></i>
        Resource is part of
      </h3>

      <resource-filtered-items
        :items="getTitleSorted(resource.partOf)"
        slotType="resource"
        icon="fas fa-copy mr-sm"
      />
    </section>

    <section :class="sectionClass">
      <h3 class="text-lg font-bold mb-sm">
        <i class="fas fa-bullseye mr-sm"></i>
        Thematically similar
      </h3>
      <p class="mb-md">
        Thematically similar resources based on terms in common of:
      </p>
      <p class="mb-lg">
        <select class="border-base p-sm"
          v-model="resourceParams.thematical"
          v-on:change="initResource(resource.id, true)">
          <option v-for="(val, key) in thematicals"
            :key="key"
            :value="key">
            {{ val }}
          </option>
        </select>
      </p>

      <div v-if="resource.similar?.length">
        <div
          v-for="(similar, key) in getTitleSorted(resource.similar)" :key="key"
          class="mb-sm"
        >
          <b-link
            :to="'/resource/' + encodeURIComponent(similar.id)"
          >
            <div class="flex items-center mb-md">
              <div class="shrink-0">
                <help-tooltip
                  :title="getResourceTypeName(similar)"
                  class="mr-sm"
                  top="0"
                  left="2rem"
                >
                  <!-- style in img is for safari bug -->
                  <img
                    :src="getResourceIcon(similar)"
                    style="width:20px;height:20px"
                    alt="icon"
                    width="20"
                    height="20">
                </help-tooltip>
              </div>

              <span class="leading-sm">{{ similar.title.text || 'No title' }}</span>
            </div>
          </b-link>
        </div>
      </div>
      <p v-else>
        No similar resources found.
      </p>
    </section>
  </div>
</template>

<script setup lang="ts">
import { onUnmounted } from 'vue';
import { $computed } from 'vue/macros';
import { resourceModule } from "@/store/modules";
import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';
import HelpTooltip from '@/component/Help/Tooltip.vue';
import ResourceFilteredItems from './FilteredItems.vue';

defineProps<{
  initResource: Function,
}>();

const sectionClass: string = 'py-base pb-sm mb-md';
const resource = $computed(() => resourceModule.getResource);
const thematicals = $computed(() => resourceModule.getThematicals);
const resourceParams = $computed(() => resourceModule.getResourceParams);

// Todo: support multiple collections later
const collection = $computed(() => Array.isArray(resource.collection) ? resource.collection[0] : resource.collection);

// reset thematical selection
onUnmounted(() => resourceModule.setResourceParamsThematical(''));

const getTitleSorted = (items: any) => {
  return utils.getSorted(items.map((s: any) => ({ ...s, text: s.title?.text ?? '' })), 'text');
}

const getResourceTypeName = (item: any): string => {
  return item.type?.[0]?.prefLabel ?? '';
}

const getResourceIcon = (item: any): string => {
  return resourceModule.getIconByTypeName(item.type?.[0]?.prefLabel);
}
</script>
