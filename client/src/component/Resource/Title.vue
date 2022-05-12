<template>
  <div>
    <div class="flex justify-between items-center">
      <!-- main title -->
      <div>
        <h1 class="text-2xl">{{ mainTitle }}</h1>
      </div>

      <!-- cts icon -->
      <div class="ml-base">
        <help-tooltip
          v-if="isCtsCertified"
          title="CoreTrustSeal Certified"
          top=".3rem"
          right="3.5rem"
        >
          <a
            href="https://www.coretrustseal.org/"
            target="_blank"
            class="w-3x h-3x"
          >
            <img
              :src="`${ generalModule.getAssetsDir }/CTS-logo.png`"
              alt="CoreTrustSeal Certified"
              class="w-full"
            >
          </a>
        </help-tooltip>
      </div>
    </div>

    <!-- other lang titles -->
    <multi-lang-info
      :nativeInfoText="nativeTitle"
      nativeInfoIconHelpText="Title in native language for this resource"
      nonNativeInfoTitleActive="Hide titles in other languages"
      nonNativeInfoTitleInactive="Show titles in other languages"
      :nonNativeInfoList="nonNativeTitles"
    />
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { generalModule, resourceModule } from "@/store/modules";
import HelpTooltip from '@/component/Help/Tooltip.vue';
import MultiLangInfo from './MultiLangInfo.vue';

const resource = $computed(() => resourceModule.getResource);
const mainTitle = $computed(() => resourceModule.getMainTitle(resource));
const isCtsCertified = $computed(() => resourceModule.getIsCtsCertified(resource));
const nativeTitle = $computed(() => resourceModule.getNativeTitle(resource));
const nonNativeTitles = $computed(() => resourceModule.getNonNativeTitles(resource));
</script>
