<template>
	<div>
    <div
      v-if="result.error"
      class="bg-white p-md text-mmd border-b-base border-red rounded-tl-base text-red"
    >
      {{ result.error }}
    </div>

    <div class="bg-white p-md text-mmd rounded-tl-base">
      <div v-if="data === true">
        <i class="fas fa-circle-notch fa-spin mr-sm backface-hidden"></i>
        Loading..
      </div>
      <div v-else-if="data && data.error" class="text-red">
        Error: Failed to load map data
      </div>
      <div v-else>
        <b>{{ totals }}</b>
        resources in the current section
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Prop } from 'vue-property-decorator';
import { search } from "@/store/modules";

@Component
export default class BrowseWhereTotals extends Vue {
  @Prop() data!: any;

  get result(): any {
    return search.getResult;
  }

  get totals() {
    let total = this.data ? (this.data?.result?.total) : (this.result?.total);
    let val = total?.value || 0;

    if (val && total?.relation !== 'eq') {
      val += '+';
    }
    return String(val).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }
}
</script>
