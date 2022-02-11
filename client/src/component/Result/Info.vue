<!-- Front page -->
<template>
  <div>
    <h2 class="text-2xl">
      Results
      <span v-if="displayResult">for "{{ displayResult }}"</span>
    </h2>
    <div v-if="result && result.total" class="text-md">
      Time: {{ result.time }}s.
      Total: {{ result.total.value }}<span v-if="result.total.relation !== 'eq'">+</span>.
      <span v-if="result.total.value">
        Page: {{ currentPage }} / {{ lastPage }}
      </span>
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import { searchModule } from "@/store/modules";

@Component
export default class ResultInfo extends Vue {

  get params(): any {
    return searchModule.getParams;
  }

  get displayResult() {
    return searchModule.displayResultString;
  }
  get result(): any {
    return searchModule.getResult;
  }

  get resultRange() {
    const start = this.currentPage < 2 ? 1 : (this.currentPage - 1) * 10 + 1;
    const end = (this.currentPage - 1) * 10 + this.result.hits.length;

    return `(${ start }-${ end })`;
  }

  get currentPage() {
    let p = parseInt(this.params.page);
    return p && p > 1 ? p : 1;
  }

  get lastPage() {
    return Math.ceil(this.result.total.value / 10);
  }
}
</script>
