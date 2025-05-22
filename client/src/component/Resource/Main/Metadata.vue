<template>
  <div>
    <h3 class="text-lg font-bold mb-lg">
      <i class="fas fa-database mr-sm"></i>
      Metadata
    </h3>

    <div v-if="resource.originalId" :class="itemClass">
      <b :class="bClass">Original ID:</b>
      <span class="break-word">{{ resource.originalId }}</span>
    </div>

    <div v-if="Array.isArray(resource.otherId) && resource.otherId.length" :class="itemClass">
      <b :class="bClass">Other ID:</b>
      <span v-for="otherId in resource.otherId" :class="{ 'block mt-xs': resource.otherId.length > 1 }">
        <span class="break-word">{{ otherId }}</span>
      </span>
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

    <div v-if="resource.language && utils.getLanguage(resource.language)" :class="itemClass">
        <b :class="bClass">Language:</b>
        <i class="fas fa-globe mr-xs"></i>
        {{ utils.getLanguage(resource.language) }}
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
      title="Resource Type"
    >
      <template v-slot="{ item }">
        <b-link :to="utils.paramsToString('/search/', { ariadneSubject: item.prefLabel })">
          <span class="relative">
            <img
              :src="getResourceIcon(item)"
              style="position:absolute;top:-3px;left:0"
              class="mr-sm"
              alt="icon"
              width="20"
              height="20"
            />
            <span class="ml-2x">
              {{ utils.sentenceCase(item.prefLabel) }}
            </span>
          </span>
        </b-link>
      </template>
    </resource-filtered-items>

    <resource-filtered-items
      :items="getSortedPlaces()"
      :class="itemClass"
      filter="placeName,country,location"
      filterUnique="placeName"
      title="Place"
    >
      <template v-slot="{ item }">
        <span v-if="item.placeName">
          <i class="fas fa-map-marker-alt mr-sm mb-xs"></i>
          <b-link
            :to="utils.paramsToString('/search', { [item.isCountry ? 'country' : 'placeName']: item.placeName })"
          >
            {{ item.isCountry ? utils.ucFirst(item.placeName) : utils.sentenceCase(item.placeName) }}
          </b-link>
        </span>
      </template>
    </resource-filtered-items>

    <resource-filtered-items
      :items="resource.publisher"
      :class="itemClass"
      slotType="organisation"
      filter="name"
      title="Publisher"
      query="publisher"
    />

    <resource-filtered-items
      :items="utils.getSorted(resource.nativeSubject, 'prefLabel')"
      :class="itemClass"
      filter="prefLabel"
      title="Original Subject"
      query="nativeSubject"
      icon="fas fa-tag mr-sm mb-xs"
      slotType="prop"
    />

    <resource-filtered-items
      :items="utils.getSorted(resource.derivedSubject, 'prefLabel')"
      :class="itemClass"
      filter="prefLabel"
      prop="prefLabel"
      query="derivedSubject"
      slotType="link"
      title="Getty AAT Subject"
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
        <b-link class="text-blue hover:underline hover:text-darkGray transition-color duration-300"
          :to="utils.paramsToString('/subject/' + utils.last(item.id.split('/')), { derivedSubject: item.prefLabel })">
          <i class="fas fa-info-circle mr-xs"></i>
        </b-link>
        {{ utils.ucFirst(item.prefLabel) }} <!--<span v-if="item.lang">({{ item.lang }})</span>-->
      </template>
    </resource-filtered-items>

    <resource-filtered-items
      :items="resource.keyword"
      :class="itemClass"
      title="Keyword"
      query="keyword"
      icon="fas fa-tag mr-sm mb-xs"
      slotType="plain"
    />

    <resource-filtered-items
      :items="resource.temporal"
      :class="itemClass"
      filter="periodName,from,until"
      title="Dating"
    >
      <template v-slot="{ item }">
        <span v-if="getPeriodo(item)">
          <span class="mr-xs" @mouseenter="toggleTooltip($event, true)" @mouseleave="toggleTooltip($event, false)">
            <i class="fas fa-question-circle text-blue hover:text-darkGray transition-color duration-300 mr-xs"/>
            <div class="fixed z-20 hidden">
              <div class="bg-blue text-white p-sm pb-xs relative">
                <div class="absolute" style="width:20px;height:30px;left:-10px;top:0"></div>
                <div class="mb-xs">
                  <b>Period:</b>&nbsp;
                  <b-link :to="utils.paramsToString('/search/', { culturalPeriods: getPeriodo(item).key, culturalLabels: getPeriodo(item).key + ':' + getPeriodo(item).filterLabel + (getPeriodo(item).region ? (' (' + utils.getCountryCode(getPeriodo(item).region) + ')') : '') })" className="text-white hover:underline">
                    <i class="fas fa-search"></i>
                    {{ getPeriodo(item).filterLabel }} ({{ getPeriodo(item).region ? utils.getCountryCode(getPeriodo(item).region) : '' }})
                  </b-link>
                </div>
                <div class="mb-xs" v-if="getPeriodo(item).region">
                  <b>Region:</b>&nbsp;
                  <b-link :to="utils.paramsToString('/search/', { temporalRegion: getPeriodo(item).region })" className="text-white hover:underline">
                    <i class="fas fa-search"></i>
                    {{ getPeriodo(item).region }}
                  </b-link>
                </div>
                <div v-if="item.from && item.until" class="mb-xs">
                  <b>Timespan:</b>&nbsp;{{ ': ' + item.from + ' to ' +  item.until }}
                </div>
                <div v-for="(label, key) in getPeriodo(item).extraLabels" v-bind:key="key" class="mb-xs">
                  <span v-if="label">
                    <b>{{ utils.sentenceCase(utils.splitCase(key)) }}:</b>&nbsp;{{ label }}<br/>
                  </span>
                </div>
              </div>
            </div>
          </span>
        </span>
        <i v-else class="far fa-clock mr-sm mb-xs"></i>
        <b-link
          v-if="item.periodName"
          :to="utils.paramsToString('/search', { temporal: item.periodName })"
        >
          {{ utils.sentenceCase(item.periodName) }}
        </b-link>
        <span v-if="item.from && item.until">
          {{ ': ' + item.from + ' to ' +  item.until }}
        </span>
      </template>
    </resource-filtered-items>

    <div v-if="resource.extent && resource.extent.length" :class="itemClass">
      <b :class="bClass">Extent:</b>
      <span>{{ resource.extent.join(', ') }}</span>
    </div>

    <div v-if="resource.resourceType" :class="itemClass">
      <b :class="bClass">Category:</b>
      <i class="fas mr-sm" :class="/collection/i.test(resource.resourceType) ? 'fa-copy' : 'fa-database'"></i>
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
      :items="resource.dataType"
      :class="itemClass"
      title="Data Type"
      filter="label"
      query="dataType"
      icon="fas fa-server mr-sm mb-xs"
      slotType="prop"
    />

    <div v-if="resource.wasCreated" :class="itemClass">
      <b :class="bClass">Created:</b>
      <span>{{ utils.formatDate(resource.wasCreated) }}</span>
    </div>

    <div v-if="resource.issued" :class="itemClass">
      <b :class="bClass">Issued:</b>
      <span>{{ utils.formatDate(resource.issued) }}</span>
    </div>

    <div v-if="resource.modified" :class="itemClass">
      <b :class="bClass">Last updated:</b>
      <span>{{ utils.formatDate(resource.modified) }}</span>
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
import BLink from '@/component/Base/Link.vue';
import HelpTooltip from '@/component/Help/Tooltip.vue';
import ResourceFilteredItems from '../FilteredItems.vue';

defineProps<{
  itemClass: string,
  bClass: string,
}>();

const resource = $computed(() => resourceModule.getResource);

const getPeriodo = (item: any) => {
  if (resource.periodo) {
    const uri = utils.last(item?.uri?.split('/') || [])?.toLowerCase();
    return resource.periodo.find((p: any) => p?.key?.toLowerCase() === uri);
  }
  return null;
}

const getSortedPlaces = () => {
  const places = (resource.spatial || []).concat((resource.country || []).map((item: any) => ({ placeName: item.name, isCountry: true })));
  return utils.getSorted(places, 'placeName');
}

const toggleTooltip = (e: any, show: boolean) => {
  const rect = e.target.getBoundingClientRect();
  e.target.firstElementChild.nextElementSibling.style.cssText = `width:300px; top: ${rect.top - 5}px; left: ${rect.left + 20}px; display:${show ? 'block' : 'none'};`;
};

const getResourceIcon = (item: any): string => {
  return resourceModule.getIconByTypeName(item.prefLabel);
}
</script>
