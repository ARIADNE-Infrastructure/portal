<!-- Front page -->
<template>
  <div class="flex items-center">
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

const options = $computed(() => searchModule.getSortOptions);
const order: string = $computed(() => searchModule.getDefaultSort().sort + '-' + searchModule.getDefaultSort().order);

const setOrder = (orderStr: string) => {
  const arr = orderStr.split('-');

  searchModule.setSearch({
    sort: arr[0],
    order: arr[1],
  });
};
</script>
