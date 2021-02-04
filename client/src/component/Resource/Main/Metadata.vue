<template>
  <div>
    <h3 class="text-lg font-bold mb-lg">
      <i class="fas fa-database mr-xs"></i>
      Metadata
    </h3>

    <div v-if="resource.originalId" :class="itemClass">
      <b class="break-word" :class="bClass">Original ID:</b>
      {{ resource.originalId }}
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
      <b :class="bClass">Language:</b>
      {{ utils.sentenceCase(synonyms.getLanguage(resource.language)) }}
    </div>

    <resource-filtered-items
      :items="resource.audience"
      :class="itemClass"
      title="Audience"
      slotType="plain"
    />

    <resource-filtered-items
      :items="resource.archaeologicalResourceType ? [resource.archaeologicalResourceType] : null"
      :class="itemClass"
      title="Resource type"
      prop="name"
      query="archaeologicalResourceType"
      slotType="prop"
    />

    <div v-if="resource.extent && resource.extent.length" :class="itemClass">
      <b :class="bClass">Extent:</b>
      {{ resource.extent.join(', ') }}
    </div>

    <resource-filtered-items
      :items="resource.aatSubjects"
      :class="itemClass"
      filter="label"
      prop="id"
      query="subjectUri"
      slotType="link"
      title="Subject">
      <span slot-scope="{ item }">
        {{ item.label }} <span v-if="item.lang">({{ item.lang }})</span>
      </span>
    </resource-filtered-items>

    <resource-filtered-items
      :items="resource.nativeSubject"
      :class="itemClass"
      filter="prefLabel"
      title="Original Subject"
      query="nativeSubject"
      slotType="prop"
    />

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
      <span slot-scope="{ item }">
        <b-link v-if="item.periodName" :to="utils.paramsToString('/search', { temporal: item.periodName })">{{ item.periodName }}</b-link><span v-if="item.from && item.until"><span v-if="item.periodName">: </span>{{ item.from + ' to ' +  item.until }}</span>
      </span>
    </resource-filtered-items>

    <resource-filtered-items
      :items="resource.spatial"
      :class="itemClass"
      filter="placeName,country,location"
      title="Place"
    >
      <span slot-scope="{ item }">
        <span v-if="item.placeName">
          <b-link :to="utils.paramsToString('/search', { q: item.placeName, fields: 'location' })">{{ item.placeName }}</b-link><span v-if="item.country">, </span>
        </span>
        <b-link v-if="item.country" :to="utils.paramsToString('/search', { q: item.country, fields: 'location' })">
          {{ utils.sentenceCase(item.country) }}
        </b-link>
        <i v-if="item.location">[{{ item.location.lat + ', ' + item.location.lon }}]</i>
      </span>
    </resource-filtered-items>

    <div v-if="resource.resourceType" :class="itemClass">
      <b :class="bClass">Type:</b>

      <b-link :to="utils.paramsToString('/search', { q: resource.resourceType })">
        {{ utils.sentenceCase(resource.resourceType) }}
      </b-link>
    </div>

    <resource-filtered-items
      :items="resource.publisher"
      :class="itemClass"
      slotType="person"
      filter="name"
      title="Publisher"
      query="publisher"
    />

    <div v-if="resource.issued" :class="itemClass">
      <b :class="bClass">Issued:</b>
      {{ utils.formatDate(resource.issued) }}
    </div>

    <div v-if="resource.modified" :class="itemClass">
      <b :class="bClass">Modified:</b>
      {{ utils.formatDate(resource.modified) }}
    </div>

    <resource-filtered-items
      :items="resource.hasMetadataRecord"
      :class="itemClass"
      :divider="true"
      filter="xmlDoc,conformsTo"
      title="Metadata record"
    >
      <div slot-scope="{ item, last }">
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
      </div>
    </resource-filtered-items>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Prop } from 'vue-property-decorator';
import { resource } from "@/store/modules";

import utils from '@/utils/utils';
import synonyms from '@/utils/synonyms';
import BLink from '@/component/Base/Link.vue';
import ResourceFilteredItems from '../FilteredItems.vue';

@Component({
  components: {
    BLink,
    ResourceFilteredItems,
  }
})
export default class ResourceMainMetadata extends Vue {
  utils = utils;
  synonyms = synonyms;

  @Prop() itemClass!: string;
  @Prop() bClass!: string;

  get resource(): any {
    return resource.getResource;
  }
}
</script>
