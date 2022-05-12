<template>
  <div>
    <!-- heading -->
    <h3 class="text-lg font-bold mb-md">
      <i class="fas fa-info-circle mr-sm" />
      Description
    </h3>

    <!-- main description -->
    {{ mainDescription }}

    <!-- other lang description -->
    <multi-lang-info
      :nativeInfoText="nativeDescription"
      nativeInfoIconHelpText="Description in native language for this resource"
      nonNativeInfoTitleActive="Hide descriptions in other languages"
      nonNativeInfoTitleInactive="Show descriptions in other languages"
      :nonNativeInfoList="nonNativeDescriptions"
    />
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { resourceModule } from "@/store/modules";
import MultiLangInfo from './MultiLangInfo.vue';

const resource = $computed(() => resourceModule.getResource);
const mainDescription = $computed(() => resourceModule.getMainDescription(resource));
const nativeDescription = $computed(() => resourceModule.getNativeDescription(resource));
const nonNativeDescriptions = $computed(() => resourceModule.getNonNativeDescriptions(resource));
</script>
