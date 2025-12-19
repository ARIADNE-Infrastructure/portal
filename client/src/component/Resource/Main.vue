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
      v-if="audioUrls.length && !errMap.audio"
      :class="sectionClass"
    >
      <resource-main-audio @error="checkError('audio', $event)" />
    </section>

    <section
      v-if="videoUrls.length && !errMap.video"
      :class="sectionClass"
    >
      <resource-main-video @error="checkError('video', $event)" />
    </section>

    <section
      v-if="iiifUrls.length && !errMap.iiifs"
      :class="sectionClass"
    >
      <resource-main-iiifs @error="checkError('iiifs', $event)" />
    </section>

    <section
      v-if="digitalImages.length && !errMap.images"
      :class="sectionClass"
    >
      <resource-main-images @error="checkError('images', $event)" />
    </section>

    <section
      v-if="objectUrls.length && !errMap.objects && !utils.isMobile()"
      :class="sectionClass"
    >
      <resource-main-objects @error="checkError('objects', $event)" />
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
import ResourceMainAudio from './Main/Audio.vue';
import ResourceMainVideo from './Main/Video.vue';
import ResourceMainImages from './Main/Images.vue';
import ResourceMainIiifs from './Main/Iiifs.vue';
import ResourceMainObjects from './Main/Objects.vue';
import utils from '@/utils/utils';

const resource = $computed(() => resourceModule.getResource);
const audioUrls = $computed(() => resourceModule.getAudioUrls(resource));
const videoUrls = $computed(() => resourceModule.getVideoUrls(resource));
const digitalImages = $computed(() => resourceModule.getDigitalImages(resource));
const iiifUrls = $computed(() => resourceModule.getIiifUrls(resource));
const objectUrls = $computed(() => resourceModule.get3dObjectUrls(resource));
const sectionClass = 'py-md mb-lg';
const itemClass = 'border-b-base border-gray mb-md pb-md last:border-b-0 last:pb-none last:mb-none';
const bClass = 'mr-sm';
const errMap = $ref({});
const checkError = (type: string, err: boolean) => errMap[type] = errMap[type] || err;
</script>
