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
          :sortKey="sortKey"
          :sortKeyOption="sortKeyOption"
          :sortKeyOptionLabel="sortKeyOptionLabel"
          :sortOrder="sortOrder"
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
                <i v-if="id === 'publisher' && findPublisher(bucket.key)"
                  class="fas fa-info-circle text-white pr-sm py-sm transition-color duration-300 hover:text-green"
                  @click.prevent.stop="() => router.push(utils.paramsToString('/publisher/' + findPublisher(bucket.key).slug, { publisher: bucket.key }))">
                </i>

                <span v-if="id === 'derivedSubjectId' && params.derivedSubjectIdLabel">
                  {{ utils.sentenceCase(params.derivedSubjectIdLabel) }}
                </span>

                <span v-else-if="id === 'fields' && fields.some(f => f.val === bucket.key)">
                  {{ fields.find(f => f.val === bucket.key).text }}
                </span>

                <span v-else>
                  {{ getFilterText(sortKey ? bucket[sortKey] : bucket.key) }}
                </span>
              </span>

              <span class="bg-white rounded-lg py-xs px-sm text-blue text-sm font-bold group-hover:text-red mr-sm">
                <i class="fa-times fas align-middle transition-color duration-300 text-sm" />
              </span>

              <span v-if="bucket.doc_count" class="rounded-lg bg-lightGray py-xs px-sm text-blue text-sm font-bold">
                {{ bucket.doc_count }}
              </span>
            </div>

            <div
              v-else
              class="flex justify-between items-center px-md py-sm text-md hover:bg-lightGray transition-all duration-300 cursor-pointer border-t-base border-gray"
              @click="setActive(id, bucket.key, true)"
            >
              <span v-if="id === 'publisher' && findPublisher(bucket.key)">
                <i class="fas fa-info-circle text-blue pr-sm py-sm transition-color duration-300 hover:text-green"
                  @click.prevent.stop="() => router.push(utils.paramsToString('/publisher/' + findPublisher(bucket.key).slug, { publisher: bucket.key }))">
                </i>
              </span>

              <span class="flex-grow break-word pr-lg">
                {{ getFilterText(sortKey ? bucket[sortKey] : bucket.key) }}
              </span>

              <span v-if="bucket.doc_count" class="whitespace-nowrap bg-lightGray rounded-lg py-xs px-sm text-midGray text-sm font-bold">
                {{ bucket.doc_count }} {{ docCountSuffix }}
              </span>

              <div v-if="bucket.extraLabels">
                <!-- show pills only for periods -->
                <span v-if="id === 'filterByCulturalPeriods'">
                  <span class="whitespace-nowrap bg-lightGray rounded-lg py-xs px-sm mx-xs text-midGray text-sm font-bold">Start: {{bucket.start}}</span>
                  <span class="whitespace-nowrap bg-lightGray rounded-lg py-xs px-sm mx-xs text-midGray text-sm font-bold">{{synonyms.getCountryCode(bucket.region)}}</span>
                </span>

                <i class="fas fa-question-circle"
                  @mouseenter="toggleTooltip($event, true)"
                  @mouseleave="toggleTooltip($event, false)"
                />

                <div class="fixed z-20 hidden bg-blue text-white p-sm">
                  <span v-for="(label, key) in bucket.extraLabels" v-bind:key="key">
                    <strong>{{ key }}:</strong> {{ label }}<br/>
                  </span>
                </div>
              </div>

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
import { useRouter } from 'vue-router'
import utils from '@/utils/utils';
import ListAccordion from '@/component/List/Accordion.vue';
import FilterAggregationOptions from './Aggregation/Options.vue';
import HelpTooltip from '@/component/Help/Tooltip.vue';
import synonyms from "@/utils/synonyms";

let height = $ref(0);
const bucketListId: string = 'bucketList-' + utils.getUniqueId();
const router = useRouter();

interface Props {
  id: string,
  item?: any,
  shortSortNames?: boolean,
  sortKey?: string,
  sortKeyOption?: string, // If set, used instead if sortKey
  sortKeyOptionLabel?: string, // Paired with sortKeyOption. If set, used instead if sortKey
  sortOrder?: string,
  sentenceCaseFilterText?: boolean,
  docCountSuffix?: '', // String suffix for doc_count in bucket aggregations
};

const { id, item, shortSortNames, sortKey, sortOrder, sentenceCaseFilterText = true, docCountSuffix } = defineProps<Props>();

const params = $computed(() => searchModule.getParams);
const fields = $computed(() => resourceModule.getFields);
const aggType = $computed(() => aggregationModule.getTypes.find(type => type.id === id));
const resultAggTitle: string = $computed(() => aggregationModule.getTitle(id));
const resultAggDescription: string = $computed(() => aggregationModule.getDescription(id));
const resultAggAnyActive: boolean = $computed(() => aggregationModule.getAnyActive(id, item?.buckets));

const hasMoreDocs: number = $computed(() => {
  const options = aggregationModule.getOptions[id];
  let count;
  if (options?.search) {
    count = options?.data?.sum_other_doc_count;
  }
  else {
    count = item?.sum_other_doc_count;
  }
  return count ?? 0;
});

const initShow: boolean = $computed(() => {
  const options = aggregationModule.getOptions[id];
  return !!(options?.search || resultAggAnyActive);
});

const filteredAndSortedBuckets: Array<any> = $computed(() => {
  let list = item?.buckets;
  const options = aggregationModule.getOptions[id];
  if (options) {
    let { search, sortBy, sortOrder, data } = options;

    // replace list with search result
    if (search && data?.buckets) {
      list = data.buckets;
    }

    // sort
    list = utils.sortListByValue(list, sortBy, sortOrder);

    // period filters needs extra sorting here to put active items at top
    if (id === 'temporalRegion' || id === 'filterByCulturalPeriods') {
      list.sort((a: any, b: any) => resultAggIsActive(id, a.key) ? -1 : 1)
    }
  }
  return list;
});

const resultAggIsActive = (key: string, bucketKey: string): boolean => {
  return aggregationModule.getIsActive(key, bucketKey);
};

const getFilterText = (text: string ): string => {
  if (aggType?.unformatted || !sentenceCaseFilterText ) {
    return text;
  }
  return utils.sentenceCase(text);
};

const findPublisher = (key: string): any => generalModule.findPublisher(key);

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

const toggleTooltip = (e: any, show: boolean) => {
  const rect = e.target.getBoundingClientRect();
  e.target.nextElementSibling.style.cssText = `width:300px; top: ${rect.top}px; left: ${rect.left + 25}px; display:${show ? 'block' : 'none'}`;
};

watch($$(filteredAndSortedBuckets), setBucketListHeight, { deep: true });
</script>
