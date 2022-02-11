<template>
  <div
    v-if="utils.objectIsNotEmpty(item.buckets) && !Object.values(item.buckets).every(b => b.key && utils.isInvalid(b.key))"
  >
    <list-accordion
      :title="resultAggTitle"
      :initShow="initShow"
      :autoShow="resultAggAnyActive"
      :hover="true"
      :height="height"
    >
      <div ref="bucketList" class="relative">
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

<script lang="ts">
import { Component, Vue, Prop, Watch } from 'vue-property-decorator';
import { searchModule, resourceModule, aggregationModule, generalModule } from "@/store/modules";
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

  height: number = 0;

  @Prop() id!: string;
  @Prop() item!: any;
  @Prop({ default: false }) shortSortNames?: boolean;

  get params(): any {
    return searchModule.getParams;
  }

  get fields(): any {
    return resourceModule.getFields;
  }

  get isLoading(): boolean {
    return generalModule.getIsLoading;
  }

  get aggType(): any {
    return aggregationModule.getTypes.find(type => type.id === this.id);
  }

  get resultAggTitle(): string {
    return aggregationModule.getTitle(this.id);
  }

  get resultAggAnyActive(): boolean {
    return aggregationModule.getAnyActive(this.id, this.item.buckets);
  }

  resultAggIsActive(key: string, bucketKey: string): boolean {
    return aggregationModule.getIsActive(key, bucketKey);
  }

  getFilterText(text: string) {
    if (this.aggType?.unformatted) {
      return text;
    }
    return utils.sentenceCase(text);
  }

  noBucketMatch(id: string): boolean {
    const options = aggregationModule.getOptionsById(id);

    return options?.data?.buckets?.length === 0 ? true : false;
  }

  get hasMoreDocs(): number {
    const options = aggregationModule.getOptionsById(this.id);
    let count;

    if (options?.search) {
      count = options?.data?.sum_other_doc_count;
    }
    else {
      count = this.item.sum_other_doc_count;
    }

    return count ?? 0;
  }

  get initShow(): boolean {
    const options = aggregationModule.getOptionsById(this.id);

    return options?.search || this.resultAggAnyActive;
  }

  get filteredAndSortedBuckets(): any[] {
    let list = this.item.buckets;
    const options = aggregationModule.getOptionsById(this.id);

    if (options) {
      let { search, sortBy, sortOrder, data } = options;

      // replace list with search result
      if (options?.search && data?.buckets) {
        list = data.buckets;
      }

      // return sorted
      return utils.sortListByValue(list, sortBy, sortOrder);
    }

    return list;
  }

  setActive(key: string, value: any, add: boolean): void {
    aggregationModule.setActive({ key, value, add });
  }

  setBucketListHeight(): void {
    const el = this.$refs.bucketList as HTMLDivElement;

    this.$nextTick(() => {
      if (el) {
        this.height = el.scrollHeight;
      }
    });
  }

  @Watch('filteredAndSortedBuckets', { deep: true })
  onLoadingChange() {
    this.setBucketListHeight();
  }
}
</script>
