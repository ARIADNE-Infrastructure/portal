<template>
  <div class="relative">
    <div
      ref="aggregations"
      class="pr-lg"
      :class="{ 'overflow-y-scroll': maxHeight }"
      :style="styles"
      @scroll.passive="setAggregationScrollPosition"
    >
      <template
        v-for="(item, id) in sortedAggs"
      >
        <filter-aggregation
          v-if="item"
          :key="id"
          :id="id"
          :item="item"
          :shortSortNames="true"
        />
      </template>
    </div>

    <div
      v-if="aggregationScrollPosition && maxHeight"
      class="absolute top-0 left-0 w-full h-lg"
      style="background-image: linear-gradient(to top, transparent, #fff);"
    >
    </div>

    <div v-if="maxHeight"
      class="absolute bottom-0 left-0 w-full h-lg"
      style="background-image: linear-gradient(to bottom, transparent, #fff);"
    >
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Prop } from 'vue-property-decorator';
import { aggregationModule } from "@/store/modules";
import FilterAggregation from './Aggregations/Aggregation.vue';

@Component({
  components: {
    FilterAggregation,
  }
})
export default class FilterAggregations extends Vue {
  @Prop() maxHeight?: string;

  aggregationScrollPosition: number = 0;

  get hasAggs(): boolean {
    return aggregationModule.hasAggs;
  }

  get sortedAggs(): any {
    return aggregationModule.getSorted;
  }

  get styles(): any {
    const styles: any = {};

    if (this.maxHeight) {
      styles.maxHeight = this.maxHeight;
    }

    return styles;
  }

  setAggregationScrollPosition(): void {
    const el = this.$refs.aggregations as HTMLDivElement;
    this.aggregationScrollPosition = el.scrollTop;
  }
}
</script>
