<template>
  <div class="lg:bg-lightGray-60 lg:p-xl lg:pb-md">
    <section>
      <h3 class="text-lg font-bold mb-lg">
        <i class="fas fa-database mr-xs"></i>
        Metadata
      </h3>

      <div v-if="resource.id" :class="itemClass">
        <b class="break-word" :class="bClass">Ariadne ID:</b>
        {{ resource.id }}
      </div>

      <div v-if="resource.originalId" :class="itemClass">
        <b class="break-word" :class="bClass">Original ID:</b>
        {{ resource.originalId }}
      </div>

      <div v-if="utils.validUrl(resource.landingPage)" :class="itemClass">
        <b :class="bClass">Landing page:</b>
        <a :href="resource.landingPage" target="_blank" class="break-word" :class="utils.linkClasses()">
          {{ resource.landingPage }}
        </a>
      </div>

      <div v-if="resource.language" :class="itemClass">
        <b :class="bClass">Language:</b>
        {{ utils.sentenceCase(resource.language) }}
      </div>

      <filtered-items
        :items="resource.audience"
        :class="itemClass"
        title="Audience"
        slotType="plain"
      />

      <filtered-items
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

      <filtered-items
        :items="resource.derivedSubject"
        :class="itemClass"
        filter="prefLabel"
        title="Subject"
        query="derivedSubject"
        slotType="prop"
      />

      <filtered-items
        :items="resource.nativeSubject"
        :class="itemClass"
        filter="prefLabel"
        title="Original Subject"
        query="nativeSubject"
        slotType="prop"
      />

      <filtered-items
        :items="resource.keyword"
        :class="itemClass"
        title="Keyword"
        query="keyword"
        slotType="plain"
      />

      <filtered-items
        :items="resource.temporal"
        :class="itemClass"
        filter="periodName,from,until"
        title="Dating">
        <span slot-scope="{ item }">
          <router-link v-if="item.periodName" :to="utils.paramsToString('/search', { temporal: item.periodName })" :class="utils.linkClasses()">{{ item.periodName }}</router-link><span v-if="item.from && item.until"><span v-if="item.periodName">: </span>{{ item.from + ' to ' +  item.until }}</span>
        </span>
      </filtered-items>

      <filtered-items
        :items="resource.spatial"
        :class="itemClass"
        filter="placeName,country,location"
        title="Place">
        <span slot-scope="{ item }">
          <span v-if="item.placeName">
            <router-link :to="utils.paramsToString('/search', { q: item.placeName, fields: 'location' })" :class="utils.linkClasses()">{{ item.placeName }}</router-link><span v-if="item.country">, </span>
          </span>
          <router-link v-if="item.country" :to="utils.paramsToString('/search', { q: item.country, fields: 'location' })" :class="utils.linkClasses()">
            {{ utils.sentenceCase(item.country) }}
          </router-link>
          <i v-if="item.location">[{{ item.location.lat + ', ' + item.location.lon }}]</i>
        </span>
      </filtered-items>

      <div v-if="resource.resourceType" :class="itemClass">
        <b :class="bClass">Type:</b>
        <router-link :to="utils.paramsToString('/search', { q: resource.resourceType })" :class="utils.linkClasses()">
          {{ utils.sentenceCase(resource.resourceType) }}
        </router-link>
      </div>

      <filtered-items
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

      <filtered-items
        :items="resource.hasMetadataRecord"
        :class="itemClass"
        :divider="true"
        filter="xmlDoc,conformsTo"
        title="Metadata record">
        <div slot-scope="{ item, last }">
          <div v-if="utils.validUrl(item.xmlDoc)" class="mt-sm" :class="{ 'border-b-base border-gray pb-md mb-md': !last }">
            <a :href="item.xmlDoc" target="_blank" class="break-word" :class="utils.linkClasses()">
              {{ item.xmlDoc }}
            </a>
          </div>
          <div v-if="item.conformsTo && item.conformsTo.length && item.conformsTo[0]">
            <div v-if="item.conformsTo[0].description" class="mt-sm whitespace-pre-line">
              {{ utils.cleanText(item.conformsTo[0].description, true) }}
            </div>
            <div v-if="item.conformsTo[0].characterSet" class="mt-sm">
              (Character set: {{ item.conformsTo[0].characterSet }})
            </div>
          </div>
        </div>
      </filtered-items>
    </section>

    <section>
      <h3 class="text-lg font-bold mb-lg mt-2x">
        <i class="fas fa-users mr-xs"></i>
        Responsible persons and organisations
      </h3>

      <filtered-items v-for="(person, key) in persons" :key="key"
        :items="resource[person.prop]"
        :class="itemClass"
        slotType="person"
        filter="name"
        :query="person.query"
        :title="person.title"
      />

      <filtered-items
        :items="resource.providedSubject"
        :class="itemClass"
        slotType="prop"
        filter="prefLabel"
        title="Provided subject"
      />

      <filtered-items
        :items="resource.aatSubjects"
        :class="itemClass"
        filter="label"
        prop="id"
        query="subjectUri"
        slotType="link"
        title="Getty AAT Subjects">
        <span slot-scope="{ item }">
          {{ item.label }} <span v-if="item.lang">({{ item.lang }})</span>
        </span>
      </filtered-items>

      <div v-if="resource.accrualPeriodicity" :class="itemClass">
        <b :class="bClass">Accrual Periodicity:</b>
        <router-link :to="utils.paramsToString('/search', { q: resource.accrualPeriodicity })" :class="utils.linkClasses()">
          {{ resource.accrualPeriodicity }}
        </router-link>
      </div>

      <div v-if="resource.contactPoint" :class="itemClass">
        <b :class="bClass">Contact point:</b>
        {{ resource.contactPoint }}
      </div>

      <div v-if="resource.id" :class="itemClass">
        <b :class="bClass">Identifier:</b>
        <a :href="'http://registry.ariadne-infrastructure.eu/dataset/' + encodeURIComponent(resource.id)" target="_blank" class="break-word" :class="utils.linkClasses()">
          http://registry.ariadne-infrastructure.eu/dataset/{{ resource.id }}
        </a>
      </div>

      <div v-if="resource.packageId" :class="itemClass">
        <b :class="bClass">Package ID:</b>
        {{ resource.packageId }}
      </div>

      <div v-if="resource.providerId" :class="itemClass">
        <b :class="bClass">Provider ID:</b>
        {{ resource.providerId }}
      </div>

      <div v-if="resource.size" :class="itemClass">
        <b :class="bClass">Size:</b>
        {{ resource.size }}
      </div>
    </section>

    <section>
      <h3 class="text-lg font-bold mb-lg mt-2x">
        <i class="fas fa-balance-scale mr-xs"></i>
        License information
      </h3>

      <div v-if="resource.accessRights && resource.accessRights !== resource.accessPolicy" :class="itemClass">
        <b :class="bClass">Access Rights:</b>
        <span :class="{ 'break-word': utils.validUrl(resource.accessRights) }">
          {{ resource.accessRights }}
        </span>
      </div>

      <div v-if="utils.validUrl(resource.accessPolicy)" :class="itemClass">
        <b :class="bClass">Access Policy:</b>
        <a :href="resource.accessPolicy" target="_blank" class="break-word" :class="utils.linkClasses()">
          {{ resource.accessPolicy }}
        </a>
      </div>
    </section>

    <section>
      <filtered-items :items="resource.distribution">
        <div slot-scope="{ item }">
          <h3 class="text-lg font-bold mb-lg mt-2x">
            <i class="fas fa-network-wired mr-xs"></i>
            Distribution
          </h3>
          <div v-if="item.title" :class="itemClass">
            <b :class="bClass">Title:</b>
            {{ item.title }}
          </div>
          <div v-if="item.description" :class="itemClass">
            <b :class="bClass">Description:</b>
            <div class="mt-sm whitespace-pre-line">
              {{ utils.cleanText(item.description, true) }}
            </div>
          </div>
          <div v-if="utils.validUrl(item.accessURL)" :class="itemClass">
            <b :class="bClass">Access URL:</b>
            <a :href="item.accessURL" target="_blank" class="break-word" :class="utils.linkClasses()">{{ item.accessURL }}</a>
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
      </filtered-items>
    </section>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Prop } from 'vue-property-decorator';
import utils from '../../utils/utils';
import FilteredItems from './FilteredItems.vue';

@Component({
  components: {
    FilteredItems
  }
})
export default class ResourceSidebar extends Vue {
  utils = utils;
  persons = [
    { prop: 'creator', title: 'Creator' },
    { prop: 'contributor', title: 'Contributor', query: 'contributor' },
    { prop: 'owner', title: 'Owner' },
    { prop: 'legalResponsible', title: 'Legal responsible' },
    { prop: 'scientificResponsible', title: 'Scientific responsible' },
    { prop: 'technicalResponsible', title: 'Technical responsible' },
  ];

  get resource () {
    return this.$store.getters.resource();
  }

  get itemClass () {
    return 'border-b-base border-gray mb-md pb-md last:border-b-0';
  }
  get bClass () {
    return 'mr-xs';
  }
}
</script>
