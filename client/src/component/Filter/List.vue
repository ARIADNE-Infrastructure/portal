<template>
  <div>
    <h2 class="text-lg mb-md">
      Filters
    </h2>

    <filter-search
      v-if="getShow('search')"
      color="blue"
      hoverStyle="hover:bg-blue-80"
      focusStyle="focus:border-blue"
      class="mb-lg"
      :breakHg="true"
      :big="true"
      :useCurrentSearch="true"
      showFields="select"
      :hasAutocomplete="true"
      :stayOnPage="true"
      size="sm"
      @submit="utils.blurMobile()"
    />

    <filter-clear
      :ignoreParams="['page', 'sort', 'order', 'mapq', 'size', 'loadingStatus', 'forceReload']"
      class="mb-lg"
    />

    <filter-years-and-periods
      v-if="getShow('yearsAndPeriods')"
      class="mb-lg"
    />

    <filter-mini-map
      v-if="getShow('miniMap')"
      :canSearch="true"
      title="Where"
      class="mb-lg"
    />

    <filter-time-line
      v-if="getShow('timeLine') && utils.objectIsNotEmpty(result) && result.hits && result.hits.length"
      :hasLoader="true"
      title="When"
      class="mb-lg"
    />

    <template v-if="getShow('aggregations')">
      <filter-aggregation
        v-for="(item, id) in sortedAggs"
        :key="id"
        :id="id"
        :item="item"
        :shortSortNames="true"
        :maxHeight="1000"
      />
    </template>

    <div style="display:none;">
      <b-link class="text-mmd hover:text-blue transition-color duration-200" :clickFn="() => advanced = !advanced">
        <div class="pt-xs pb-md">
          <i class="fas fa-chevron-right cursor-pointer mr-xs duration-200" :class="{ 'transform rotate-90': advanced }"></i>
          Advanced filters
        </div>
      </b-link >
    </div>
    <div class="text-mmd" style="display:none;">
      <transition v-on:before-enter="transitionLeave" v-on:enter="transitionEnter" v-on:before-leave="transitionEnter" v-on:leave="transitionLeave">
        <div v-show="advanced" class="ease-out duration-200">
          <div class="flex justify-between items-center py-sm px-md border-base border-gray">
            <p>Filter and search operator:</p>
            <b-select
              :value="operator"
              :options="operatorOptions"
              :maxWidth="50"
              color="blue"
              @input="setOperator"
            />
          </div>
        </div>
      </transition>
    </div>
  </div>
</template>

<script setup lang="ts">
import { watch, onMounted } from 'vue';
import { $computed } from 'vue/macros';
import { useRoute, onBeforeRouteLeave } from 'vue-router'
import { searchModule, aggregationModule } from "@/store/modules";
import utils from '@/utils/utils';
import router from '@/router';
import BLink from '@/component/Base/Link.vue';
import BSelect from '@/component/Base/Select.vue';
import FilterSearch from '@/component/Filter/Search.vue';
import FilterYearsAndPeriods from '@/component/Filter/YearsAndPeriods.vue';
import FilterClear from '@/component/Filter/Clear.vue';
import FilterMiniMap from '@/component/Filter/MiniMap.vue';
import FilterTimeLine from '@/component/Filter/TimeLine.vue';
import FilterAggregation from '@/component/Filter/Aggregation.vue';

const props = defineProps<{
  show: Array<string>,
}>();

let isUnmounted: boolean = false;
const route = useRoute();
const result = $computed(() => searchModule.getResult);
const sortedAggs = $computed(() => aggregationModule.getSorted);
const operatorOptions = $computed(() => searchModule.getOperatorOptions);
const operator = $computed(() => searchModule.getOperator);
let advanced: boolean = $ref(false);

const getShow = (component: string): boolean => !!props.show?.includes(component);
const setOperator = (val: string) => searchModule.setSearch({ operator: val });
const transitionEnter = (el: HTMLElement) => el.style.opacity = '1';
const transitionLeave = (el: HTMLElement) => el.style.opacity = '0';

onMounted(() => advanced = operator === 'or');

watch(route, () => {
  if (!isUnmounted && router.currentRoute.value.path !== '/browse/where') {
    searchModule.setAggregationSearch(router.currentRoute.value.query);
  }
}, { immediate: true })

onBeforeRouteLeave(() => isUnmounted = true);
</script>
