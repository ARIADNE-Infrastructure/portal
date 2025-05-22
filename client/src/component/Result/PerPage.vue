<template>
  <div class="flex items-center">
    <h3 class="font-bold mr-md text-mmd">Per page</h3>
    <div>
      <b-select
        :value="perPage"
        :options="options"
        :maxWidth="45"
        color="blue"
        @input="setPerPage"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { searchModule } from "@/store/modules";
import BSelect from '@/component/Base/Select.vue';

const options = $computed(() => searchModule.getPerPageOptions);
const perPage = $computed(() => searchModule.getPerPage);

const setPerPage = (value: string) => {
  searchModule.setSearch({
    size: value === '20' ? '' : value,
    page: 0
  });
};
</script>
