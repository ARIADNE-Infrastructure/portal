<template>
  <div
    v-if="utils.objectIsNotEmpty(item?.buckets) && !Object.values(item.buckets).every(b => b.key && utils.isInvalid(b.key))"
  >
    <list-accordion
      :title="resultAggTitle"
      :description="resultAggDescription"
      :initShow="initShow"
      :autoShow="resultAggAnyActive"
      :hover="true"
      :height="height"
      :maxHeight="maxHeight"
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
          :getMore="getMore"
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
              <span class="flex-grow break-word pr-lg items-center" style="display:flex">
                <i v-if="id === 'publisher' && findPublisher(bucket.key)"
                  class="fas fa-info-circle text-white pr-sm py-sm transition-color duration-300 hover:text-green"
                  @click.prevent.stop="() => router.push(utils.paramsToString('/publisher/', { publisher: bucket.key }))">
                </i>
                <i v-else-if="id === 'derivedSubject'"
                  class="fas fa-info-circle text-white pr-sm py-sm transition-color duration-300 hover:text-green"
                  @click.prevent.stop="() => router.push(utils.paramsToString('/subject/' + bucket.key, { derivedSubject: bucket.key }))">
                </i>

                <span v-if="id === 'fields' && fields.some(f => f.val === bucket.key)">
                  {{ fields.find(f => f.val === bucket.key).text }}
                </span>

                <span v-else class="flex items-center">
                  <img v-if="id === 'ariadneSubject'"
                    :src="getResourceIcon(bucket.key)"
                    class="mr-sm invert"
                    alt="icon"
                    width="25"
                    height="25"
                  />
                  {{ getFilterText(sortKey ? bucket[sortKey] : bucket.key) }}
                </span>
              </span>

              <span class="bg-white rounded-lg py-xs px-sm text-blue text-sm font-bold group-hover:text-red">
                <i class="fa-times fas align-middle transition-color duration-300 text-sm" />
              </span>

              <span v-if="bucket.doc_count" class="rounded-lg bg-lightGray py-xs px-sm text-blue text-sm font-bold ml-sm">
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
                  @click.prevent.stop="() => router.push(utils.paramsToString('/publisher/', { publisher: bucket.key }))">
                </i>
              </span>
              <span v-else-if="id === 'derivedSubject'">
                <i class="fas fa-info-circle text-blue pr-sm py-sm transition-color duration-300 hover:text-green"
                  @click.prevent.stop="() => router.push(utils.paramsToString('/subject/' + bucket.key, { derivedSubject: bucket.key }))">
                </i>
              </span>
              <img v-else-if="id === 'ariadneSubject'"
                :src="getResourceIcon(bucket.key)"
                class="mr-sm"
                alt="icon"
                width="25"
                height="25"
              />

              <span class="flex-grow break-word pr-lg">
                {{ getFilterText(sortKey ? bucket[sortKey] : bucket.key) }}
                <span v-if="id === 'culturalPeriods' && bucket.region" class="text-midGray">
                  ({{ utils.getCountryCode(bucket.region) }})
                </span>
              </span>

              <span v-if="id === 'culturalPeriods' && bucket.doc_count">
                <span class="whitespace-nowrap bg-lightGray rounded-lg py-xs px-sm mx-xs text-midGray text-sm font-bold">Start: {{ bucket.start }}</span>
              </span>

              <span v-if="bucket.doc_count" class="whitespace-nowrap bg-lightGray rounded-lg py-xs px-sm text-midGray text-sm font-bold">
                {{ bucket.doc_count }} {{ docCountSuffix }}
              </span>

              <div v-if="bucket.extraLabels" class="ml-sm">
                <i class="fas fa-question-circle"
                  @mouseenter="toggleTooltip($event, true)"
                  @mouseleave="toggleTooltip($event, false)"
                />

                <div class="fixed z-20 hidden bg-blue text-white p-sm">
                  <span v-for="(label, key) in bucket.extraLabels" v-bind:key="key">
                    <span v-if="label">
                      <strong>{{ utils.sentenceCase(utils.splitCase(key)) }}:</strong> {{ label }}<br/>
                    </span>
                  </span>
                </div>
              </div>

            </div>
          </div>
        </div>

        <div
          v-if="noBucketMatch(id)"
          class="bg-red text-white p-sm text-md"
        >
          No results found.
        </div>

        <div
          v-else-if="hasMoreDocs"
          class="bg-yellow text-white p-sm text-md cursor-pointer hover:bg-green transition-background duration-300"
          @click="getMore++"
        >
          Get 20 more results..
        </div>
      </div>
    </list-accordion>
  </div>
</template>

<script setup lang="ts">
import { watch, nextTick } from 'vue';
import { $ref, $computed, $$ } from 'vue/macros';
import { resourceModule, aggregationModule, generalModule, searchModule } from "@/store/modules";
import { useRouter, onBeforeRouteLeave } from 'vue-router'
import utils from '@/utils/utils';
import ListAccordion from '@/component/List/Accordion.vue';
import FilterAggregationOptions from './Aggregation/Options.vue';

let height = $ref(0);
const bucketListId: string = 'bucketList-' + utils.getUniqueId();
const router = useRouter();

interface Props {
  id: string,
  item?: any,
  shortSortNames?: boolean,
  sortKey?: string,
  sortKeyOption?: string, // If set, used instead if sortKey
  sortKeyOptionLabel?: string, // Paired with sortKeyOption. If set, used instead of sortKey
  sortOrder?: string,
  sentenceCaseFilterText?: boolean,
  docCountSuffix?: '', // String suffix for doc_count in bucket aggregations
  maxHeight?: number,
};

const { id, item, shortSortNames, sortKey, sortOrder, sentenceCaseFilterText = true, docCountSuffix, maxHeight } = defineProps<Props>();

const fields = $computed(() => resourceModule.getFields);
const aggType = $computed(() => aggregationModule.getTypes.find(type => type.id === id));
const resultAggTitle: string = $computed(() => aggregationModule.getTitle(id));
const resultAggDescription: string = $computed(() => aggregationModule.getDescription(id));
const resultAggAnyActive: boolean = $computed(() => aggregationModule.getAnyActive(id, item?.buckets));
let getMore = $ref(0);

const hasMoreDocs: number = $computed(() => {
  const options = aggregationModule.getOptions[id];
  let count;
  if (options?.search || (getMore && options?.data?.buckets)) {
    count = options?.data?.sum_other_doc_count;
  } else {
    count = item?.sum_other_doc_count;
  }
  return count ?? 0;
});

const initShow: boolean = $computed(() => {
  const options = aggregationModule.getOptions[id];
  return !!(options?.search || resultAggAnyActive);
});

const filteredAndSortedBuckets: Array<any> = $computed(() => {
  let list = item?.buckets || [];
  const options = aggregationModule.getOptions[id];
  if (options && ((options.search || getMore) && options.data?.buckets)) {
    list = options.data.buckets;
  }

  // check missing from params (if clicks get 20 more, or have from search won't show from result set)
  const params = searchModule.getParams[id];
  if (params) {
    params.split('|').forEach((val: string) => {
      const lcVal = val.trim().toLowerCase();
      if (!list.some((b: any) => (b.key || '').trim().toLowerCase() === lcVal)) {
        list = list.concat([{ key: val, doc_count: 0 }]);
      }
    });
  }

  // sort
  if (options) {
    let { sortBy, sortOrder } = options;
    list = utils.sortListByValue(list, sortBy, sortOrder);
  }
  const active = list.filter((it: any) => resultAggIsActive(id, it.key));
  if (active.length) {
    list = active.concat(list.filter((it: any) => !resultAggIsActive(id, it.key)));
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
  return options?.data?.buckets?.length === 0 && options?.search;
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

const getResourceIcon = (name: string): string => {
  return resourceModule.getIconByTypeName(name);
};

watch($$(filteredAndSortedBuckets), setBucketListHeight, { deep: true });

onBeforeRouteLeave(() => aggregationModule.setOptionsToDefault());
</script>
