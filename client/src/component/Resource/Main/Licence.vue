<template>
  <div>
    <h3 class="text-lg font-bold mb-lg">
      <i class="fas fa-balance-scale mr-sm"></i>
      Licence information
    </h3>

    <div
      v-if="resource.accessRights && resource.accessRights !== resource.accessPolicy"
      :class="itemClass"
    >
      <b :class="bClass">Access Rights:</b>

      <b-link v-if="utils.validUrl(resource.accessRights)"
        :href="resource.accessRights"
        target="_blank"
        class="break-word"
        :useDefaultStyle="true">
        {{ resource.accessRights }}
      </b-link>
      <span v-else class="break-word">
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

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { resourceModule } from "@/store/modules";
import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';

defineProps<{
  itemClass: string,
  bClass: string,
}>();

const resource = $computed(() => resourceModule.getResource);
</script>
