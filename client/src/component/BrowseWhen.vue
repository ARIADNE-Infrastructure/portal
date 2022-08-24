<template>
	<div class="pt-3x px-base max-w-screen-xl mx-auto relative flex flex-col lg:flex-row">
    <!-- filters (mobile & desktop) -->
    <template v-if="window.innerWidth">
      <filter-toggleable v-if="window.innerWidth < 1000" class="lg:w-1/3 lg:pr-lg">
        <filter-list :show="['search', 'yearsAndPeriods', 'aggregations']" />
      </filter-toggleable>
      <aside v-else class="lg:w-1/3 lg:pr-lg">
        <filter-list :show="['search', 'yearsAndPeriods', 'aggregations']" />
      </aside>
    </template>

    <!-- main content -->
    <section class="lg:w-2/3 lg:pl-lg">
      <h2 class="text-2xl">Browse when</h2>
      <p class="mb-lg">Scroll on the timeline to zoom. Drag to pan. Hold shift and drag a selection to apply a time range. Click "Display as search result" to search the time range in the catalogue.</p>

      <p v-if="result.error" class="text-red">
        {{ result.error }}
      </p>

      <div
        v-else
        ref="timeline"
      >
        <filter-time-line :hasLoader="true" />
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { watch, onMounted } from 'vue';
import { $computed } from 'vue/macros';
import { useRoute, onBeforeRouteLeave } from 'vue-router'

import { generalModule, searchModule } from "@/store/modules";
import utils from '@/utils/utils';
import FilterList from '@/component/Filter/List.vue';
import FilterToggleable from '@/component/Filter/Toggleable.vue';
import FilterTimeLine from '@/component/Filter/TimeLine.vue';

const route = useRoute();
const window = $computed(() => generalModule.getWindow);
const result = $computed(() => searchModule.getResult);

onMounted(() => {
  searchModule.setSearch({
    fromRoute: true
  });
});

const unwatch = watch(route, () => {
  searchModule.setSearch({ fromRoute: true });
});

onBeforeRouteLeave(unwatch);
</script>
