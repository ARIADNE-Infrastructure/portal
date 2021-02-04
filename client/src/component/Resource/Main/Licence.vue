<template>
  <div>
    <h3 class="text-lg font-bold mb-lg">
      <i class="fas fa-balance-scale mr-xs"></i>
      License information
    </h3>

    <div
      v-if="resource.accessRights && resource.accessRights !== resource.accessPolicy"
      :class="itemClass"
    >
      <b :class="bClass">Access Rights:</b>

      <span :class="{ 'break-word': utils.validUrl(resource.accessRights) }">
        {{ resource.accessRights }}
      </span>
    </div>

    <div
      v-if="utils.validUrl(resource.accessPolicy)"
      :class="itemClass"
    >
      <b :class="bClass">Access Policy:</b>

      <b-link
        :href="resource.accessPolicy"
        target="_blank"
        class="break-word"
        :useDefaultStyle="true"
      >
        {{ resource.accessPolicy }}
      </b-link>
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Prop } from 'vue-property-decorator';
import { resource } from "@/store/modules";

import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';

@Component({
  components: {
    BLink,
  }
})
export default class ResourceMainLicence extends Vue {
  utils = utils;

  @Prop() itemClass!: string;
  @Prop() bClass!: string;

  get resource(): any {
    return resource.getResource;
  }
}
</script>