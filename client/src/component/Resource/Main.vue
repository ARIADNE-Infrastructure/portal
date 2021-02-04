<template>
  <div>
    <section :class="sectionClass">
      <resource-main-metadata
        :itemClass="itemClass"
        :bClass="bClass"
      />
    </section>

    <section :class="sectionClass">
      <resource-main-responsible
        :itemClass="itemClass"
        :bClass="bClass"
      />
    </section>

    <section :class="sectionClass">
      <resource-main-licence
        :itemClass="itemClass"
        :bClass="bClass"
      />
    </section>

    <section
      v-if="resource.distribution"
      :class="sectionClass"
    >
      <resource-main-distribution
        :itemClass="itemClass"
        :bClass="bClass"
      />
    </section>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Prop } from 'vue-property-decorator';
import { resource } from "@/store/modules";
import utils from '@/utils/utils';

import ResourceMainMetadata from './Main/Metadata.vue';
import ResourceMainResponsible from './Main/Responsible.vue';
import ResourceMainLicence from './Main/Licence.vue';
import ResourceMainDistribution from './Main/Distribution.vue';

@Component({
  components: {
    ResourceMainMetadata,
    ResourceMainResponsible,
    ResourceMainLicence,
    ResourceMainDistribution,
  }
})
export default class ResourceMain extends Vue {
  utils = utils;

  get resource(): any {
    return resource.getResource;
  }

  get sectionClass () {
    return 'py-md rounded-base mb-lg';
  }

  get itemClass () {
    return 'border-b-base border-gray mb-md pb-md last:border-b-0 last:pb-none last:mb-none';
  }

  get bClass () {
    return 'mr-xs';
  }
}
</script>
