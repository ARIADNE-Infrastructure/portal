<template>
  <div>
    <!-- heading -->
    <h3 class="text-lg font-bold mb-md">
      <i class="fas fa-info-circle mr-xs" />
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

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import { resourceModule } from "@/store/modules";
import MultiLangInfo from './MultiLangInfo.vue';

@Component({
  components: {
    MultiLangInfo,
  },
})
export default class ResourceDescription extends Vue {
  resourceModule = resourceModule;

  get resource(): any {
    return resourceModule.getResource;
  }

  get mainDescription (): string {
    return resourceModule.getMainDescription(this.resource);
  }

  get nativeDescription (): string {
    return resourceModule.getNativeDescription(this.resource);
  }

  get nonNativeDescriptions (): any[] {
    return resourceModule.getNonNativeDescriptions(this.resource);
  }
}
</script>