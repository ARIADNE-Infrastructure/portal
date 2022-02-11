<template>
  <div>
    <div class="pr-lg mt-lg lg:mt-none">
      <div class="hidden lg:block">
        <h2
          class="text-lg mb-md">
          Filters
        </h2>

        <filter-search
          color="blue"
          hoverStyle="hover:bg-blue-80"
          focusStyle="focus:border-blue"
          class="mb-lg"
          :breakHg="true"
          :big="true"
          :useCurrentSearch="true"
          :hasAutocomplete="true"
          showFields="select"
          @submit="utils.blurMobile()"
        />
      </div>

      <result-mini-map
        title="Where"
        :noZoom="utils.isMobile()"
      />

      <filter-time-line
        v-if="utils.objectIsNotEmpty(result) && result.hits && result.hits.length"
        title="When"
      />
    </div>

    <filter-aggregations />

    <div class="pr-lg">
      <filter-clear
        :ignoreParams="['page', 'sort', 'order', 'mapq', 'loadingStatus', 'forceReload']"
      />
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import { searchModule } from "@/store/modules";
import utils from '@/utils/utils';

import FilterSearch from '@/component/Filter/Search.vue';
import ResultMiniMap from './MiniMap.vue';
import FilterTimeLine from '@/component/Filter/TimeLine.vue';
import FilterAggregations from '@/component/Filter/Aggregations.vue';
import FilterClear from '@/component/Filter/Clear.vue';

@Component({
  components: {
    FilterSearch,
    ResultMiniMap,
    FilterTimeLine,
    FilterAggregations,
    FilterClear,
  }
})
export default class ResultSidebar extends Vue {
  utils: any = utils;

  get result(): any {
    return searchModule.getResult;
  }
}
</script>
