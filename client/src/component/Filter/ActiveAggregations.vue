<template>
  <div
    v-if="items.length"
    class="mb-lg"
  >
    <div
      v-for="(item, key) in items"
      :key="key"
      @click="removeActive(item.key, item.bucket.key)"
      class="flex justify-between items-center text-white bg-blue hover:bg-blue-90 group transition-all duration-300 cursor-pointer p-md border-b-base"
    >
      <span class="flex-grow break-word pr-lg">
        <b>{{ resultAggTitle(item.key) }}</b>:

        <span v-if="item.key === 'subjectUri' && params.subjectLabel">
          {{ utils.sentenceCase(params.subjectLabel) }}
        </span>

        <span v-else-if="item.key === 'fields' && fields.some(f => f.val === item.bucket.key)">
          {{ fields.find(f => f.val === item.bucket.key).text }}
        </span>

        <span v-else>
          {{ utils.sentenceCase(item.bucket.key) }}
        </span>
      </span>

      <span class="bg-white rounded-lg py-xs px-sm text-blue text-sm font-bold group-hover:text-red">
        <i class="fa-times fas align-middle transition-color duration-300" />
      </span>
    </div>

    <div
      class="mt-sm cursor-pointer inline-block hover:text-red transition-color duration-300"
      @click="clearAll"
    >
      <span class="mr-xs">Clear all items</span>
      <i class="fa-times fas align-middle transition-color duration-300 text-red" />
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import { search, aggregation, resource } from "@/store/modules";
import utils from '@/utils/utils';

@Component
export default class FilterActiveAggregations extends Vue {
  utils: any = utils;

  get result() {
    return search.getResult;
  }

  get params() {
    return search.getParams;
  }

  get fields() {
    return resource.getFields;
  }

  get items () {
    let items = [];

    if (aggregation.hasAggs) {
      for (let aggKey in this.result.aggs) {
        let agg = this.result.aggs[aggKey];
        const anyActive = aggregation.getAnyActive(aggKey, agg.buckets);

        if (utils.objectIsNotEmpty(agg.buckets) && anyActive) {
          for (let key in agg.buckets) {
            let bucket = agg.buckets[key];

            if (aggregation.getIsActive(aggKey, bucket.key)) {
              items.push({
                key: aggKey,
                bucket: bucket,
              });
            }
          }
        }
      }
    }

    return items;
  }

  resultAggTitle(key: string) {
    return aggregation.getTitle(key);
  }

  clearAll () {
    search.setSearch({
      clear: true,
      q: this.params.q
    })
  }

  removeActive(key: string, value: any) {
    aggregation.setActive({ key, value, add: false });
  }
}
</script>
