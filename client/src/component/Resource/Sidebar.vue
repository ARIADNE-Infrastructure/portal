<template>
  <div>
    <section
      v-if="resource.collection && resource.collection.hits && resource.collection.hits.length"
      :class="sectionClass"
    >
      <h3 class="text-lg font-bold mb-lg">
        <i class="fas fa-copy mr-sm"></i>
        Resource has {{ resource.collection.total }} records
      </h3>

      <resource-filtered-items
        :items="resource.collection.hits"
        slotType="resource"
      />

      <b-link
        v-if="resource.collection.total"
        :to="utils.paramsToString('/search', { isPartOf: resource.identifier, isPartOfLabel: resource.title ? resource.title.text : '' })"
        class="mb-sm block"
        :useDefaultStyle="true"
      >
        <i class="fas fa-search mr-sm"></i>
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
      v-if="resource.partOf && resource.partOf.length"
      :class="sectionClass"
    >
      <h3 class="text-lg font-bold mb-lg">
        <i class="fas fa-copy mr-sm"></i>
        Resource is part of
      </h3>

      <resource-filtered-items
        :items="resource.partOf"
        slotType="resource"
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

      <div v-if="resource.similar && resource.similar.length">
        <div
          v-for="(similar, key) in resource.similar" :key="key"
          class="mb-sm"
        >
          <b-link
            :to="'/resource/' + encodeURIComponent(similar.id)"
          >
            <help-tooltip
              :title="getResourceTypeName(similar)"
              class="mr-sm"
              top="0"
              left="2rem"
            >
              <!-- style in img is for safari bug -->
              <img
                :src="getResourceIconTemporary(similar)"
                style="width:20px;height:20px"
                class="inline mb-xs"
                alt="icon"
                width="20"
                height="20">
            </help-tooltip>
            <span>{{ similar.title.text || 'No title' }}</span>
          </b-link>
        </div>
      </div>
      <p v-else>
        No similar resources found.
      </p>
    </section>

    <section
      v-if="(Array.isArray(resource.derivedSubject) && resource.derivedSubject.some(d => d.prefLabel)) ||
      (Array.isArray(resource.temporal) && resource.temporal.some(t => t.periodName)) ||
      (Array.isArray(resource.spatial) && resource.spatial.some(s => s.placeName))"
      :class="sectionClass"
    >
      <h3 class="text-lg font-bold mb-lg">
        <i class="fas fa-tags mr-sm"></i>
        Tags
      </h3>

      <resource-filtered-items
        :items="resource.derivedSubject"
        slotType="prop"
        filter="prefLabel"
        query="derivedSubject"
        icon="fas fa-tag mr-sm mb-xs"
      />

      <resource-filtered-items
        :items="resource.temporal"
        slotType="prop"
        filter="periodName"
        query="temporal"
        icon="far fa-clock mr-sm mb-xs"
      />

      <resource-filtered-items
        :items="resource.spatial"
        slotType="prop"
        filter="placeName"
        query="placeName"
        icon="fas fa-map-marker-alt mr-sm mb-xs"
      />
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

const sectionClass: string = 'py-base pb-sm rounded-base mb-md';
const resource = $computed(() => resourceModule.getResource);
const thematicals = $computed(() => resourceModule.getThematicals);
const resourceParams = $computed(() => resourceModule.getResourceParams);

// reset thematical selection
onUnmounted(() => resourceModule.setResourceParamsThematical(''));

const getResourceTypeName = (item: any): string => {
  return item.type?.[0]?.prefLabel ?? '';
}

const getResourceIcon = (item: any): string => {
  return resourceModule.getIconByTypeId(item.type.id);
}

const getResourceIconTemporary = (item: any): string => {
  return resourceModule.getIconByTypeNameTemporary(item.type?.[0]?.prefLabel);
}
</script>
