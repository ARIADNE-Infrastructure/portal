<!-- Front page -->
<template>
  <div>
    <h2 class="text-lg mb-md">Current search</h2>

    <search
      color="blue"
      bg="bg-blue"
      hover="hover:bg-blue-80"
      focus="focus:border-blue"
      class="mb-xl"
      :big="true"
      :useCurrentSearch="true"
      :showFields="true"
    />

    <h2 class="text-lg mb-md" v-if="utils.objectIsNotEmpty(result)">
      Filters
    </h2>

    <div v-if="utils.objectIsNotEmpty(result.aggs)">
      <div v-for="(agg, aggKey) in result.aggs" v-bind:key="aggKey">
        <div v-if="utils.objectIsNotEmpty(agg.buckets) && hasActiveItem(aggKey, agg.buckets)">
          <div v-for="(bucket, key) in agg.buckets" v-bind:key="key" :class="key === agg.buckets.length - 1 ? 'mb-md' : ''">
            <div v-if="isActive(aggKey, bucket.key)"
              v-on:click="setSearch(aggKey, bucket.key, false)"
              class="flex justify-between items-center text-white bg-blue hover:bg-blue-90 transition-all duration-300 cursor-pointer p-md border-b-base">
              <span class="flex-grow break-word pr-lg">
                <b>{{ titles[aggKey] || utils.sentenceCase(utils.splitCase(aggKey)) }}</b>:
                <span v-if="aggKey === 'subjectUri' && params.subjectLabel">{{ utils.sentenceCase(params.subjectLabel) }}</span>
                <span v-else>{{ utils.sentenceCase(bucket.key) }}</span>
              </span>
              <span class="bg-white rounded-lg py-xs px-sm text-blue text-sm font-bold hover:text-red">
                <i class="fa-times fas align-middle transition-color duration-300" />
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <search-map
      title="Where"
      height="250px"
      :isMultiple="true"
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
          v-if="utils.objectIsNotEmpty(agg.buckets)"
        >
          <accordion
            :title="titles[aggKey] || utils.sentenceCase(utils.splitCase(aggKey))"
            :initShow="hasActiveItem(aggKey, agg.buckets)"
            :hover="true"
          >
            <div
              v-for="(bucket, key) in agg.buckets"
              v-bind:key="key"
            >
              <div v-if="bucket.key && bucket.key.trim()">
                <div
                  v-if="isActive(aggKey, bucket.key)"
                  @click="setSearch(aggKey, bucket.key, false)"
                  class="flex justify-between items-center text-white bg-blue hover:bg-blue-90 transition-all duration-300 cursor-pointer p-md border-t-base"
                >
                  <span class="flex-grow break-word pr-lg">
                    <span v-if="aggKey === 'subjectUri' && params.subjectLabel">{{ utils.sentenceCase(params.subjectLabel) }}</span>
                    <span v-else>{{ utils.sentenceCase(bucket.key) }}</span>
                  </span>

                  <span class="bg-white rounded-lg py-xs px-sm text-blue text-sm font-bold hover:text-red">
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
  titles = {
    archaeologicalResourceType: 'Resource type',
    spatial: 'Place',
    nativeSubject: 'Subject',
    derivedSubject: 'Original subject',
    temporal: 'Dating'
  }

  get result () {
    return this.$store.getters.result();
  }
  get params () {
    return this.$store.getters.params();
  }

  getSortedAggs () {
    let startOrder = ['archaeologicalResourceType', 'nativeSubject', 'keyword', 'publisher', 'contributor'],
        sorted = {};

    startOrder.forEach((key: string) => sorted[key] = this.result.aggs[key]);
    for (let key in this.result.aggs) {
      if (!sorted[key]) {
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
