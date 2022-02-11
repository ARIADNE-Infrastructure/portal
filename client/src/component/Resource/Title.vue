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

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import { generalModule, resourceModule } from "@/store/modules";
import HelpTooltip from '@/component/Help/Tooltip.vue';
import MultiLangInfo from './MultiLangInfo.vue';

@Component({
  components: {
    HelpTooltip,
    MultiLangInfo,
  },
})
export default class ResourceTitle extends Vue {
  generalModule = generalModule;
  resourceModule = resourceModule;

  get resource(): any {
    return resourceModule.getResource;
  }

  get mainTitle (): string {
    return resourceModule.getMainTitle(this.resource);
  }

  get isCtsCertified(): boolean {
    return resourceModule.getIsCtsCertified(this.resource);
  }

  get nativeTitle (): string {
    return resourceModule.getNativeTitle(this.resource);
  }

  get nonNativeTitles (): any[] {
    return resourceModule.getNonNativeTitles(this.resource);
  }
}
</script>