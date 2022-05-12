<template>
  <div
    v-if="utils.objectIsNotEmpty(item.buckets) && !Object.values(item.buckets).every(b => b.key && utils.isInvalid(b.key))"
  >
    <list-accordion
      :title="resultAggTitle"
      :description="resultAggDescription"
      :initShow="initShow"
      :autoShow="resultAggAnyActive"
      :hover="true"
      :height="height"
    >
      <div :id="bucketListId" class="relative">
        <filter-aggregation-options
          :id="id"
          :title="resultAggTitle"
          :shortSortNames="shortSortNames"
          @searchUpdate="setBucketListHeight"
        />

        <div
          v-for="(bucket, key) in filteredAndSortedBuckets"
          v-bind:key="key"
        >
          <div v-if="bucket.key && String(bucket.key).trim() && !utils.isInvalid(bucket.key)">
            <div
              v-if="resultAggIsActive(id, bucket.key)"
              class="flex justify-between items-center px-md py-sm text-md text-white bg-blue hover:bg-blue-90 group transition-all duration-300 cursor-pointer border-t-base"
              @click="setActive(id, bucket.key, false)"
            >
              <span class="flex-grow break-word pr-lg">
                <span v-if="id === 'derivedSubjectId' && params.derivedSubjectIdLabel">
                  {{ utils.sentenceCase(params.derivedSubjectIdLabel) }}
                </span>

                <span v-else-if="id === 'fields' && fields.some(f => f.val === bucket.key)">
                  {{ fields.find(f => f.val === bucket.key).text }}
                </span>

                <span v-else>
                  {{ getFilterText(bucket.key) }}
                </span>
              </span>

              <span class="bg-white rounded-lg py-xs px-sm text-blue text-sm font-bold group-hover:text-red mr-sm">
                <i class="fa-times fas align-middle transition-color duration-300 text-sm" />
              </span>

              <span class="bg-midGray rounded-lg py-xs px-sm text-white text-sm font-bold">
                {{ bucket.doc_count }}
              </span>
            </div>

            <div
              v-else
              class="flex justify-between items-center px-md py-sm text-md hover:bg-lightGray transition-all duration-300 cursor-pointer border-t-base border-gray"
              @click="setActive(id, bucket.key, true)"
            >
              <span class="flex-grow break-word pr-lg">
                {{ getFilterText(bucket.key) }}
              </span>

              <span class="bg-midGray rounded-lg py-xs px-sm text-white text-sm font-bold">
                {{ bucket.doc_count }}
              </span>
            </div>
          </div>
        </div>

        <div
          v-if="noBucketMatch(id)"
          class="bg-red text-white p-sm text-md rounded-b-base"
        >
          No results found.
        </div>

        <div
          v-else-if="hasMoreDocs"
          class="bg-red text-white p-sm text-md rounded-b-base"
        >
          Some options are currently not visible. Use the search field above to narrow down this list.
        </div>
      </div>
    </list-accordion>
  </div>
</template>

<script setup lang="ts">
import { watch, nextTick } from 'vue';
import { $ref, $computed, $$ } from 'vue/macros';
import { searchModule, resourceModule, aggregationModule, generalModule } from "@/store/modules";
import utils from '@/utils/utils';
import ListAccordion from '@/component/List/Accordion.vue';
import FilterAggregationOptions from './Aggregation/Options.vue';

let height = $ref(0);
const bucketListId: string = 'bucketList-' + utils.getUniqueId();

const props = defineProps({
  id: { type: String, required: true },
  item: Object,
  shortSortNames: Boolean,
});

const params = $computed(() => searchModule.getParams);
const fields = $computed(() => resourceModule.getFields);
const aggType = $computed(() => aggregationModule.getTypes.find(type => type.id === props.id));
const resultAggTitle: string = $computed(() => aggregationModule.getTitle(props.id));
const resultAggDescription: string = $computed(() => aggregationModule.getDescription(props.id));
const resultAggAnyActive: boolean = $computed(() => aggregationModule.getAnyActive(props.id, props.item?.buckets));

const hasMoreDocs: number = $computed(() => {
  const options = aggregationModule.getOptions[props.id];
  let count;

  if (options?.search) {
    count = options?.data?.sum_other_doc_count;
  }
  else {
    count = props.item?.sum_other_doc_count;
  }

  return count ?? 0;
});

const initShow: boolean = $computed(() => {
  const options = aggregationModule.getOptions[props.id];
  return !!(options?.search || resultAggAnyActive);
});

const filteredAndSortedBuckets: Array<any> = $computed(() => {
  let list = props.item?.buckets;
  const options = aggregationModule.getOptions[props.id];

  if (options) {
    let { search, sortBy, sortOrder, data } = options;

    // replace list with search result
    if (search && data?.buckets) {
      list = data.buckets;
    }

    // return sorted
    return utils.sortListByValue(list, sortBy, sortOrder);
  }

  return list;
});

const resultAggIsActive = (key: string, bucketKey: string): boolean => {
  return aggregationModule.getIsActive(key, bucketKey);
};

const getFilterText = (text: string): string => {
  if (aggType?.unformatted) {
    return text;
  }
  return utils.sentenceCase(text);
};

const noBucketMatch = (id: string): boolean => {
  const options = aggregationModule.getOptions[id];
  return options?.data?.buckets?.length === 0;
};

const setActive = (key: string, value: any, add: boolean) => {
  aggregationModule.setActive({ key, value, add });
};

const setBucketListHeight = () => {
  nextTick(() => {
    height = document.getElementById(bucketListId)?.scrollHeight || 0;
  });
};

watch($$(filteredAndSortedBuckets), setBucketListHeight, { deep: true });
</script>
