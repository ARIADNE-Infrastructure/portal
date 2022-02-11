<template>
  <div>
    <h3 class="text-lg font-bold mb-lg">
      <i class="fas fa-users mr-xs"></i>
      Responsible persons and organisations
    </h3>

    <resource-filtered-items v-for="(person, key) in persons" :key="key"
      :items="resource[person.prop]"
      :class="itemClass"
      slotType="person"
      filter="name"
      :query="person.query"
      :title="person.title"
    />

    <resource-filtered-items
      :items="resource.providedSubject"
      :class="itemClass"
      slotType="prop"
      filter="prefLabel"
      title="Provided subject"
    />

    <div v-if="resource.accrualPeriodicity" :class="itemClass">
      <b :class="bClass">Accrual Periodicity:</b>
      <b-link :to="utils.paramsToString('/search', {q: resource.accrualPeriodicity})">
        {{resource.accrualPeriodicity}}
      </b-link>
    </div>

    <div v-if="resource.contactPoint" :class="itemClass">
      <b :class="bClass">Contact point:</b>
      {{ resource.contactPoint }}
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
  </div>
</template>

<script lang="ts">
import { Component, Vue, Prop } from 'vue-property-decorator';
import { resourceModule } from "@/store/modules";

import BLink from '@/component/Base/Link.vue';
import ResourceFilteredItems from '../FilteredItems.vue';

@Component({
  components: {
    BLink,
    ResourceFilteredItems,
  }
})
export default class ResourceMainResponsible extends Vue {
  persons = [
    { prop: 'creator', title: 'Creator', query: 'creator' },
    { prop: 'contributor', title: 'Contributor', query: 'contributor' },
    { prop: 'owner', title: 'Owner', query: 'owner' },
    { prop: 'responsible', title: 'Responsible', query: 'responsible' }
  ];

  @Prop() itemClass!: string;
  @Prop() bClass!: string;

  get resource(): any {
    return resourceModule.getResource;
  }



}
</script>
