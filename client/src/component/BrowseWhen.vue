<template>
	<div class="pt-3x px-base max-w-screen-xl mx-auto relative flex flex-col lg:flex-row">
    <aside class="pt-base lg:pt-none lg:w-1/3 lg:pr-lg lg:order-first block order-last relative">
      <h2 class="text-lg mb-md">
        Filters
      </h2>

      <filter-search
        color="blue"
        hoverStyle="hover:bg-blue-80"
        focusStyle="focus:border-blue"
        class="mb-lg pr-lg"
        :breakHg="true"
        :big="true"
        :useCurrentSearch="true"
        showFields="select"
        :hasAutocomplete="true"
        :stayOnPage="true"
        @submit="utils.blurMobile()"
      />

      <filter-years class="mb-lg pr-lg" />

      <filter-aggregations max-height="490px" />

      <div class="pr-lg">
        <filter-clear
          :ignoreParams="['page', 'sort', 'order', 'mapq', 'bbox', 'size', 'ghp', 'loadingStatus', 'forceReload']"
        />
      </div>
    </aside>

    <section class="lg:w-2/3 lg:pl-lg block">
      <p v-if="result.error" class="text-red">
        {{ result.error }}
      </p>

      <div
        v-else
        ref="timeline"
      >
        <filter-time-line />
      </div>
    </section>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import { searchModule } from "@/store/modules";
import utils from '@/utils/utils';

import FilterSearch from '@/component/Filter/Search.vue';
import FilterYears from '@/component/Filter/Years.vue';
import FilterAggregations from '@/component/Filter/Aggregations.vue';
import FilterTimeLine from '@/component/Filter/TimeLine.vue';
import FilterClear from '@/component/Filter/Clear.vue';

@Component({
  components: {
    FilterSearch,
    FilterYears,
    FilterAggregations,
    FilterTimeLine,
    FilterClear,
  }
})
export default class BrowseWhen extends Vue {
  utils = utils;

  get result(): any {
    return searchModule.getResult;
  }

  async created () {
    await searchModule.setSearch({
      fromRoute: true
    });
  }
}
</script>