<template>
  <div>
    <!-- native info -->
    <div
      v-if="nativeInfoText"
      class="border-t-base border-gray mt-md pt-md"
    >
      <help-tooltip
        :title="nativeInfoIconHelpText"
        top="-.6rem"
        left="1.25rem"
        messageClasses="bg-blue text-white"
      >
        <i class="fa fa-globe mr-sm text-blue" />
      </help-tooltip>

      {{ nativeInfoText }}
    </div>

    <!-- non-native descriptions -->
    <list-accordion
      v-if="nonNativeInfoList.length"
      :titleActive="nonNativeInfoTitleActive"
      :titleInactive="nonNativeInfoTitleInactive"
      class="mt-md"
    >
      <ul class="p-md">
        <li
          v-for="(item, index) in nonNativeInfoList"
          :key="index"
          class="border-b-base border-gray mb-md pb-md last:border-b-0 last:pb-none last:mb-none"
        >
          <span
            v-if="item.language"
            class="font-bold"
          >
            {{ utils.getLanguage(item.language) }}:
          </span>

          <p>{{ utils.cleanText(item.text, true) }}</p>
        </li>
      </ul>
    </list-accordion>
  </div>
</template>

<script setup lang="ts">
import utils from '@/utils/utils';
import HelpTooltip from '@/component/Help/Tooltip.vue';
import ListAccordion from '@/component/List/Accordion.vue';

defineProps<{
  nativeInfoText: string,
  nativeInfoIconHelpText: string,
  nonNativeInfoTitleActive: string,
  nonNativeInfoTitleInactive: string,
  nonNativeInfoList: Array<any>,
}>();
</script>
