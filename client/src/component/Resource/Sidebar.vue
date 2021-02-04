<template>
  <div>
    <section
      v-if="resource.collection && resource.collection.hits && resource.collection.hits.length"
      :class="sectionClass"
    >
      <h3 class="text-lg font-bold mb-lg">
        <i class="fas fa-copy mr-xs"></i>
        Resource has parts
      </h3>

      <resource-filtered-items
        :items="resource.collection.hits"
        slotType="resource"
      />

      <b-link
        v-if="resource.collection.total"
        :to="utils.paramsToString('/search', { q: 'isPartOf:' + resource.id })"
        class="mb-sm block"
        useDefaultStyle="true"
      >
        <i class="fas fa-search mr-xs"></i>
        All parts ({{ resource.collection.total }})
      </b-link>
    </section>

    <section
      v-if="resource.partof"
      :class="sectionClass"
    >
      <h3 class="text-lg font-bold mb-lg">
        <i class="fas fa-copy mr-xs"></i>
        Resource is part of
      </h3>

      <resource-filtered-items
        :items="resource.partof"
        slotType="resource"
      />
    </section>

    <section :class="sectionClass">
      <h3 class="text-lg font-bold mb-sm">
        <i class="fas fa-bullseye mr-xs"></i>
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
        <div v-for="(similar, key) in resource.similar" :key="key"
          class="mb-sm">
          <b-link
            :to="'/resource/' + encodeURIComponent(similar.id)">
            <help-tooltip
            :title="similar.type.name"
            class="mr-xs"
            top="10px">
              <!-- style in img is for safari bug -->
              <img
                :src="getResourceIconTemporary(similar)"
                style="width:20px;height:20px"
                class="inline mb-xs"
                alt="icon"
                width="20"
                height="20">
            </help-tooltip>
            <span>{{ similar.title || 'No title' }}</span>
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
        <i class="fas fa-tags mr-xs"></i>
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
        fields="location"
        icon="fas fa-map-marker-alt mr-sm mb-xs"
      />
    </section>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Prop } from 'vue-property-decorator';
import { resource } from "@/store/modules";

import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';
import HelpTooltip from '@/component/Help/Tooltip.vue';
import ResourceFilteredItems from './FilteredItems.vue';

@Component({
  components: {
    BLink,
    HelpTooltip,
    ResourceFilteredItems,
  }
})
export default class ResourceSidebar extends Vue {
  @Prop() initResource!: Function;
  utils = utils;

  get resource(): any {
    return resource.getResource;
  }

  get thematicals (): any {
    return resource.getThematicals;
  }
  get resourceParams (): string {
    return resource.getResourceParams;
  }

  get sectionClass () {
    return 'py-base pb-sm rounded-base mb-md';
  }

  getResourceIcon(item: any): string {
    return resource.getIconByTypeId(item.type.id);
  }

  getResourceIconTemporary(item: any): string {
    return resource.getIconByTypeNameTemporary(item.type.name);
  }
}
</script>
