<template>
  <div
    v-if="utils.objectIsNotEmpty(item.buckets) && !Object.values(item.buckets).every(b => b.key && utils.isInvalid(b.key))"
  >
    <list-accordion
      :title="resultAggTitle(id)"
      :initShow="resultAggAnyActive(id, item.buckets)"
      :autoShow="resultAggAnyActive(id, item.buckets)"
      :hover="true"
      :height="height"
    >
      <div ref="bucketList" class="relative">
        <filter-aggregation-options
          :id="id"
          :title="resultAggTitle(id)"
          :shortSortNames="shortSortNames"
          @searchUpdate="setBucketListHeight"
        />

        <div
          v-for="(bucket, key) in filteredAndSortedBuckets(id, item.buckets)"
          v-bind:key="key"
        >
          <div v-if="bucket.key && String(bucket.key).trim() && !utils.isInvalid(bucket.key)">
            <div
              v-if="resultAggIsActive(id, bucket.key)"
              class="flex justify-between items-center px-md py-sm text-md text-white bg-blue hover:bg-blue-90 group transition-all duration-300 cursor-pointer border-t-base"
              @click="setActive(id, bucket.key, false)"
            >
              <span class="flex-grow break-word pr-lg">
                <span v-if="id === 'subjectUri' && params.subjectLabel">
                  {{ utils.sentenceCase(params.subjectLabel) }}
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
      </div>
    </list-accordion>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Prop, Watch } from 'vue-property-decorator';
import { search, resource, aggregation, general } from "@/store/modules";
import utils from '@/utils/utils';

import ListAccordion from '@/component/List/Accordion.vue';
import FilterAggregationOptions from './Aggregation/Options.vue';

@Component({
  components: {
    ListAccordion,
    FilterAggregationOptions,
  }
})
export default class FilterAggregation extends Vue {
  utils: any = utils;

  private height: number = 0;

  @Prop() id!: string;
  @Prop() item!: any;
  @Prop({ default: false }) shortSortNames?: boolean;

  get params(): any {
    return search.getParams;
  }

  get fields(): any {
    return resource.getFields;
  }

  get isLoading(): boolean {
    return general.getLoading;
  }

  get aggType(): any {
    return aggregation.getTypes.find(type => type.id === this.id);
  }

  resultAggTitle(key: string): string {
    return aggregation.getTitle(key);
  }

  resultAggAnyActive(key: string, items: string[]): boolean {
    return aggregation.getAnyActive(key, items);
  }

  resultAggIsActive(key: string, bucketKey: string): boolean {
    return aggregation.getIsActive(key, bucketKey);
  }

  getFilterText(text: string) {
    if (this.aggType?.unformatted) {
      return text;
    }
    return utils.sentenceCase(text);
  }

  filteredAndSortedBuckets(id: string, list: any[]): any {
    let result = list;
    const options = aggregation.getOptionsById(id);

    // options not yet set; return as is
    if (!options) {
      return result;
    }

    // options set; get params
    const { search, sortBy, sortOrder } = options;

    // run search filter
    result = result.filter((item: any) => {
      const key = String(item.key);

      return key.toLowerCase().includes(search.toLowerCase());
    });

    // run ordering
    result = utils.sortListByValue(result, sortBy, sortOrder);

    return result;
  }

  setActive(key: string, value: any, add: boolean): void {
    aggregation.setActive({ key, value, add });
  }

  setBucketListHeight(): void {
    const el = this.$refs.bucketList as HTMLDivElement;

    this.$nextTick(() => {
      if (el) {
        this.height = el.scrollHeight;
      }
    });
  }

  @Watch('isLoading')
  onLoadingChange() {
    this.setBucketListHeight();
  }
}
</script>
