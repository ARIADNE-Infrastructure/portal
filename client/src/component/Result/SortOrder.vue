<!-- Front page -->
<template>
  <div class="flex items-center">
    <div class="p-sm pr-sm">
      <help-tooltip top="-.25rem" left="1rem" messageClasses="bg-blue text-white group-hover:z-25" customStyle="white-space:normal;width:300px">
        <i class="fas fa-question-circle" style="transform:translateY(-1px)" />
        <template v-slot:content>
          <ul class="p-sm -mb-md">
            <li v-for="helpText of orderHelpTexts" :key="helpText.title" class="mb-md">
              <strong>{{ helpText.title }}</strong>
              <div v-html="helpText.text" />
            </li>
          </ul>
        </template>
      </help-tooltip>
    </div>
    <h3 class="font-bold mr-md text-mmd">
      Order
    </h3>
    <b-select
      :value="order"
      :options="options"
      :minWidth="185"
      color="blue"
      @input="setOrder"
    />
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { searchModule } from "@/store/modules";
import BSelect from '@/component/Base/Select.vue';
import HelpTooltip from '@/component/Help/Tooltip.vue';

const options = $computed(() => searchModule.getSortOptions);
const order: string = $computed(() => searchModule.getDefaultSort().sort + '-' + searchModule.getDefaultSort().order);
const orderHelpTexts = $computed(() => searchModule.getOrderHelpTexts);

const setOrder = (orderStr: string) => {
  const arr = orderStr.split('-');

  searchModule.setSearch({
    sort: arr[0],
    order: arr[1],
  });
};
</script>
