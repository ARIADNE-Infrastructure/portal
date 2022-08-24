<template>
  <div>
    <h3 class="text-lg font-bold mb-lg">
      <i class="fas fa-database mr-sm"></i>
      Metadata
    </h3>

    <div v-if="resource.originalId" :class="itemClass">
      <b class="break-word" :class="bClass">Original ID:</b>
      <b-link
        v-if="utils.validUrl(resource.originalId)"
        :href="resource.originalId"
        target="_blank"
        class="break-word"
        :useDefaultStyle="true"
      >
        {{ resource.originalId }}
      </b-link>
      <span v-else>{{ resource.originalId }}</span>
    </div>

    <div v-if="utils.validUrl(resource.landingPage)" :class="itemClass">
      <b :class="bClass">Landing page:</b>

      <b-link
        :href="resource.landingPage"
        target="_blank"
        class="break-word"
        :useDefaultStyle="true"
      >
        {{ resource.landingPage }}
      </b-link>
    </div>

    <div v-if="resource.language" :class="itemClass">
      <b :class="bClass">Language:</b> {{ synonyms.getLanguage(resource.language) }}
    </div>

    <resource-filtered-items
      :items="resource.audience"
      :class="itemClass"
      title="Audience"
      slotType="plain"
    />

    <resource-filtered-items
      :items="resource.ariadneSubject"
      :class="itemClass"
      title="Resource type"
      prop="prefLabel"
      query="ariadneSubject"
      slotType="prop"
    />

    <div v-if="resource.extent && resource.extent.length" :class="itemClass">
      <b :class="bClass">Extent:</b>
      {{ resource.extent.join(', ') }}
    </div>

    <div class="flex border-b-base border-gray pb-md mb-md">
      <div
        v-if="resource.derivedSubject"
        class="flex-1"
      >
        <resource-filtered-items
          :items="resource.derivedSubject"
          :class="itemClass"
          filter="prefLabel"
          prop="id"
          query="derivedSubjectId"
          slotType="link"
          title="Subject - AAT"
        >
          <template v-slot:helpIcon>
            <help-tooltip
              title="Read more about AAT"
              top="0"
              left="1.25rem"
            >
              <a
                href="https://www.getty.edu/research/tools/vocabularies/aat/about.html"
                target="_blank"
              >
                <i class='fas fa-question-circle ml-xs'/>
              </a>
            </help-tooltip>
          </template>

          <template v-slot="{ item }">
            {{ item.prefLabel }} <span v-if="item.lang">({{ item.lang }})</span>
          </template>
        </resource-filtered-items>
      </div>

      <div
        v-if="resource.nativeSubject"
        class="flex-1"
      >
        <resource-filtered-items
          :items="resource.nativeSubject"
          :class="itemClass"
          filter="prefLabel"
          title="Subject - Original"
          query="nativeSubject"
          slotType="prop"
        />
      </div>
    </div>

    <resource-filtered-items
      :items="resource.keyword"
      :class="itemClass"
      title="Keyword"
      query="keyword"
      slotType="plain"
    />

    <resource-filtered-items
      :items="resource.temporal"
      :class="itemClass"
      filter="periodName,from,until"
      title="Dating"
    >
      <template v-slot="{ item }">
        <b-link
          v-if="item.periodName"
          :to="utils.paramsToString('/search', { temporal: item.periodName })"
        >
          {{ utils.sentenceCase(item.periodName) }}{{ item.periodName ? ': ' : '' }}
        </b-link>

        <template v-if="item.from && item.until">
          {{ item.from + ' to ' +  item.until }}
        </template>
      </template>
    </resource-filtered-items>

    <resource-filtered-items
      :items="resource.spatial"
      :class="itemClass"
      filter="placeName,country,location"
      title="Place"
    >
      <template v-slot="{ item }">
        <span v-if="item.placeName">
          <b-link
            :to="utils.paramsToString('/search', {placeName: item.placeName})"
          >
            {{ utils.sentenceCase(item.placeName) }}
          </b-link>
        </span>
      </template>
    </resource-filtered-items>

    <div v-if="resource.resourceType" :class="itemClass">
      <b :class="bClass">Type:</b>

      <b-link :to="utils.paramsToString('/search', { resourceType: resource.resourceType })">
        {{ utils.sentenceCase(resource.resourceType) }}
      </b-link>

      <b-link
        v-if="resource.has_type"
        :href="resource.has_type.uri"
        target="_blank"
      >
        ({{ utils.sentenceCase(resource.has_type.label) }})
      </b-link>
    </div>

    <resource-filtered-items
      :items="resource.publisher"
      :class="itemClass"
      slotType="organisation"
      filter="name"
      title="Publisher"
      query="publisher"
    />

    <div v-if="resource.wasCreated" :class="itemClass">
      <b :class="bClass">Created:</b>
      {{ utils.formatDate(resource.wasCreated) }}
    </div>

    <div v-if="resource.issued" :class="itemClass">
      <b :class="bClass">Issued:</b>
      {{ utils.formatDate(resource.issued) }}
    </div>

    <div v-if="resource.modified" :class="itemClass">
      <b :class="bClass">Last updated:</b>
      {{ utils.formatDate(resource.modified) }}
    </div>

    <resource-filtered-items
      :items="resource.hasMetadataRecord"
      :class="itemClass"
      :divider="true"
      filter="xmlDoc,conformsTo"
      title="Metadata record"
    >
      <template v-slot="{ item, last }">
        <div v-if="utils.validUrl(item.xmlDoc)" class="mt-sm" :class="{ 'border-b-base border-gray pb-md mb-md': !last }">
          <b-link
            :href="item.xmlDoc"
            target="_blank"
            class="break-word"
            :useDefaultStyle="true"
          >
            {{ item.xmlDoc }}
          </b-link>
        </div>

        <div v-if="item.conformsTo && item.conformsTo.length && item.conformsTo[0]">
          <div v-if="item.conformsTo[0].description" class="mt-sm whitespace-pre-line">{{ utils.cleanText(item.conformsTo[0].description, true) }}</div>
          <div v-if="item.conformsTo[0].characterSet" class="mt-sm">
            (Character set: {{ item.conformsTo[0].characterSet }})
          </div>
        </div>
      </template>
    </resource-filtered-items>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { resourceModule } from "@/store/modules";
import utils from '@/utils/utils';
import synonyms from '@/utils/synonyms';
import BLink from '@/component/Base/Link.vue';
import HelpTooltip from '@/component/Help/Tooltip.vue';
import ResourceFilteredItems from '../FilteredItems.vue';

defineProps<{
  itemClass: string,
  bClass: string,
}>();

const resource = $computed(() => resourceModule.getResource);
</script>
