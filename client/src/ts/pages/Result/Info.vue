<!-- Front page -->
<template>
  <div>
    <h2 class="text-2x">
      Results
      <span v-if="params.q">for "{{ params.q }}"</span>
    </h2>
    <div v-if="result && result.total" class="text-md">
      Time: {{ result.time }}s.
      Total: {{ result.total.value }}<span v-if="result.total.relation !== 'eq'">+</span>.
      <span v-if="result.total.value">
        Page: {{ currentPage }}/{{ lastPage }}
        {{ resultRange }}.
      </span>
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';

@Component
export default class Info extends Vue {
  get params() {
    return this.$store.getters.params();
  }
  get result() {
    return this.$store.getters.result();
  }

  get resultRange() {
    const start = this.currentPage < 2 ? 1 : (this.currentPage - 1) * 10 + 1;
    const end = (this.currentPage - 1) * 10 + this.result.hits.length;

    return `(${ start }-${ end })`;
  }

  get currentPage() {
    let p = parseInt(this.params.page)
    return p && p > 1 ? p : 1;
  }

  get lastPage() {
    return Math.ceil(this.result.total.value / 10);
  }
}
</script>
