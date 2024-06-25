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

    <section
      v-if="digitalImages.length"
      :class="sectionClass"
    >
      <resource-main-images />
    </section>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { resourceModule } from "@/store/modules";
import ResourceMainMetadata from './Main/Metadata.vue';
import ResourceMainResponsible from './Main/Responsible.vue';
import ResourceMainLicence from './Main/Licence.vue';
import ResourceMainDistribution from './Main/Distribution.vue';
import ResourceMainImages from './Main/Images.vue';

const resource = $computed(() => resourceModule.getResource);
const digitalImages: Array<string> = $computed(() => resourceModule.getDigitalImages(resource));
const sectionClass: string = 'py-md mb-lg';
const itemClass: string = 'border-b-base border-gray mb-md pb-md last:border-b-0 last:pb-none last:mb-none';
const bClass: string = 'mr-sm';
</script>
