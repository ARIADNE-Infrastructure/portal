<template>
  <div>
    <div class="hidden lg:block">
      <h2 class="text-lg mb-md">Current search</h2>

      <search
        color="blue"
        bg="bg-blue"
        hover="hover:bg-blue-80"
        focus="focus:border-blue"
        class="mb-2x"
        :breakHg="true"
        :big="true"
        :useCurrentSearch="true"
        :showFields="true"
        v-on:submit="utils.blurMobile()"
      />
    </div>

    <h2 class="text-lg mb-md" v-if="utils.objectIsNotEmpty(result) && result.hits && result.hits.length">
      Filters
    </h2>

    <div v-if="activeFilters.length" class="mb-lg">
      <div v-for="(filter, key) in activeFilters" :key="key"
        v-on:click="setSearch(filter.key, filter.bucket.key, false)"
        class="flex justify-between items-center text-white bg-blue hover:bg-blue-90 group transition-all duration-300 cursor-pointer p-md border-b-base">
        <span class="flex-grow break-word pr-lg">
          <b>{{ aggTitles[filter.key] || utils.sentenceCase(utils.splitCase(filter.key)) }}</b>:
          <span v-if="filter.key === 'subjectUri' && params.subjectLabel">
            {{ utils.sentenceCase(params.subjectLabel) }}
          </span>
          <span v-else-if="filter.key === 'fields' && fields.some(f => f.val === filter.bucket.key)">
            {{ fields.find(f => f.val === filter.bucket.key).text }}
          </span>
          <span v-else>
            {{ utils.sentenceCase(filter.bucket.key) }}
          </span>
        </span>
        <span class="bg-white rounded-lg py-xs px-sm text-blue text-sm font-bold group-hover:text-red">
          <i class="fa-times fas align-middle transition-color duration-300" />
        </span>
      </div>

      <div class="mt-sm cursor-pointer inline-block hover:text-red transition-color duration-300"
        v-on:click="resetFilters">
        <span class="mr-xs">Clear all filters</span>
        <i class="fa-times fas align-middle transition-color duration-300 text-red" />
      </div>
    </div>

    <search-map
      title="Where"
      height="250px"
      :isMultiple="true"
      :noZoom="utils.isMobile()"
    />

    <time-line
      title="When"
    />

    <div
      v-if="utils.objectIsNotEmpty(result.aggs)"
    >
      <div
        v-for="(agg, aggKey) in getSortedAggs()"
        v-bind:key="aggKey"
      >
        <div
          v-if="utils.objectIsNotEmpty(agg.buckets) && !Object.values(agg.buckets).every(b => b.key && utils.isInvalid(b.key))"
        >
          <accordion
            :title="aggTitles[aggKey] || utils.sentenceCase(utils.splitCase(aggKey))"
            :initShow="hasActiveItem(aggKey, agg.buckets)"
            :hover="true"
          >
            <div
              v-for="(bucket, key) in agg.buckets"
              v-bind:key="key"
            >
              <div v-if="bucket.key && bucket.key.trim() && !utils.isInvalid(bucket.key)">
                <div
                  v-if="isActive(aggKey, bucket.key)"
                  @click="setSearch(aggKey, bucket.key, false)"
                  class="flex justify-between items-center text-white bg-blue hover:bg-blue-90 group transition-all duration-300 cursor-pointer p-md border-t-base"
                >
                  <span class="flex-grow break-word pr-lg">
                    <span v-if="aggKey === 'subjectUri' && params.subjectLabel">
                      {{ utils.sentenceCase(params.subjectLabel) }}
                    </span>
                    <span v-else-if="aggKey === 'fields' && fields.some(f => f.val === bucket.key)">
                      {{ fields.find(f => f.val === bucket.key).text }}
                    </span>
                    <span v-else>
                      {{ utils.sentenceCase(bucket.key) }}
                    </span>
                  </span>

                  <span class="bg-white rounded-lg py-xs px-sm text-blue text-sm font-bold group-hover:text-red">
                    <i class="fa-times fas align-middle transition-color duration-300" />
                  </span>
                </div>

                <div
                  v-else
                  @click="setSearch(aggKey, bucket.key, true)"
                  class="flex justify-between items-center hover:bg-lightGray transition-all duration-300 cursor-pointer border-t-base border-gray p-md"
                >
                  <span class="flex-grow break-word pr-lg">
                    {{ utils.sentenceCase(bucket.key) }}
                  </span>

                  <span class="bg-midGray rounded-lg py-xs px-sm text-white text-sm font-bold">
                    {{ bucket.doc_count }}
                  </span>
                </div>
              </div>
            </div>
          </accordion>
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import utils from '../../utils/utils';
import Accordion from '../../components/Form/Accordion.vue';
import Search from '../../components/Form/Search.vue';
import SearchMap from '../../components/Form/SearchMap.vue';
import TimeLine from '../../components/Form/TimeLine.vue';

@Component({
  components: {
    Accordion,
    Search,
    SearchMap,
    TimeLine,
  }
})
export default class ResultSidebar extends Vue {
  utils: any = utils;

  get result () {
    return this.$store.getters.result();
  }
  get params () {
    return this.$store.getters.params();
  }
  get fields () {
    return this.$store.getters.fields();
  }
  get aggTitles () {
    return this.$store.getters.aggTitles();
  }

  get activeFilters () {
    let filters = [];

    if (utils.objectIsNotEmpty(this.result.aggs)) {
      for (let aggKey in this.result.aggs) {
        let agg = this.result.aggs[aggKey];
        if (utils.objectIsNotEmpty(agg.buckets) && this.hasActiveItem(aggKey, agg.buckets)) {
          for (let key in agg.buckets) {
            let bucket = agg.buckets[key];
            if (this.isActive(aggKey, bucket.key)) {
              filters.push({
                key: aggKey,
                bucket: bucket,
              });
            }
          }
        }
      }
    }
    return filters;
  }

  resetFilters () {
    this.$store.dispatch('setSearch', {
      clear: true,
      q: this.params.q
    })
  }

  getSortedAggs () {
    let startOrder = ['archaeologicalResourceType', 'nativeSubject', 'keyword', 'publisher', 'contributor'],
        sorted = {};

    startOrder.forEach((key: string) => sorted[key] = this.result.aggs[key]);
    for (let key in this.result.aggs) {
      if (!sorted[key] && key !== 'geogrid') {
        sorted[key] = this.result.aggs[key];
      }
    }
    return sorted;
  }

  setSearch (key: string, val: any, add: boolean) {
    if (this.params[key]) {
      let aggs = this.params[key].split('|');

      if (add) {
        aggs.push(val)

      } else {
        aggs = aggs.filter((agg: any) => {
          return String(agg || '').toLowerCase() !== String(val || '').toLowerCase();
        });
      }

      val = aggs.join('|');
    }

    this.$store.dispatch('setSearch', {
      [key]: val,
      page: 0
    });
  }

  hasActiveItem (key: string, items: Array<string>): boolean {
    return items.some((item: any) => this.isActive(key, item.key));
  }

  isActive (key: string, bucketKey: string): boolean {
    if (this.params[key]) {
      return this.params[key].split('|').some((k: any) => {
        return String(k || '').toLowerCase() === String(bucketKey || '').toLowerCase();
      });
    }
    return false;
  }
}
</script>
