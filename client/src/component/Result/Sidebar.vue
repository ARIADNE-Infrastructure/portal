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
          bg="bg-blue"
          hover="hover:bg-blue-80"
          focus="focus:border-blue"
          class="mb-lg"
          :breakHg="true"
          :big="true"
          :useCurrentSearch="true"
          :clearSearch="true"
          :showFields="true"
          @submit="utils.blurMobile()"
        />
      </div>

      <result-map
        title="Where"
        height="250px"
        :isMultiple="true"
        :noZoom="utils.isMobile()"
      />

      <filter-time-line title="When" v-if="utils.objectIsNotEmpty(result) && result.hits && result.hits.length" />
    </div>

    <filter-aggregations />

    <div class="pr-lg">
      <filter-clear />
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import { search, aggregation } from "@/store/modules";
import utils from '@/utils/utils';

import FilterSearch from '@/component/Filter/Search.vue';
import ResultMap from './Map.vue';
import FilterTimeLine from '@/component/Filter/TimeLine.vue';
import FilterAggregations from '@/component/Filter/Aggregations.vue';
import FilterClear from '@/component/Filter/Clear.vue';

@Component({
  components: {
    FilterSearch,
    ResultMap,
    FilterTimeLine,
    FilterAggregations,
    FilterClear,
  }
})
export default class ResultSidebar extends Vue {
  utils: any = utils;

  get result(): any {
    return search.getResult;
  }
}
</script>
