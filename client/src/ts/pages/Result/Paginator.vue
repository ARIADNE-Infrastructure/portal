<!-- Paginator for search results -->
<template>
  <div class="flex" v-if="active">
    <span
      v-for="(page, key) in getPaging()"
      v-bind:key="key"
      class="leading-none mx-none my-none py-md px-base text-mmd border-base transition-all duration-300"
      :class="pageClasses(page)"
      :title="'page ' + (page.val < 2 ? 1 : page.val)"
      v-on:click.prevent="changePage(page.val, page.hover)"
      v-html="page.label"
    >
    </span>
  </div>
</template>

<script lang="ts">
import { Vue, Component, Prop, Watch } from 'vue-property-decorator';

@Component
export default class Paginator extends Vue {
  get params () {
    return this.$store.getters.params();
  }
  get result () {
    return this.$store.getters.result();
  }
  get currentPage () {
    return parseInt(this.params.page) || 1;
  }
  get active () {
    let val = this.result?.total?.value;
    return val && parseInt(val) > 10;
  }

  pageClasses (page: any): string {
    let classes = page.active ? 'bg-blue border-blue text-white' : 'border-gray text-blue';

    if (page.hover) {
      classes += ' hover:bg-gray-30 cursor-pointer';
    }
    if (page.cl) {
      classes += ' ' + page.cl;
    }
    return classes;
  }

  // changes page, only if can
  changePage (page: any, can: boolean) {
    if (can) {
      this.$store.dispatch('setSearch', { page: page < 2 ? 0 : page })
    }
  }

  // returns paging array
  getPaging (): Array<any> {
    let total = parseInt(this.result.total.value),
        pages: Array<any> = [],
        start = this.currentPage - 2,
        max = Math.ceil(total / 10),
        amount = 5;

    if (amount > max) {
      amount = max
    }
    if (start < 2) {
      start = 1
    }
    if (start + amount > max) {
      start = max - amount + 1;
    }

    let activePage = this.currentPage < 2 ? 1 : this.currentPage;

    for (let i = start; i < start + amount; i++) {
      pages.push({
        active: i === activePage,
        hover: i !== activePage,
        label: i,
        val: i
      });
    }

    pages.unshift({ label: '<i class="fas fa-angle-double-left"></i>', val: 1, hover: activePage > 1, cl: 'rounded-l-base' });
    pages.push({ label: '<i class="fas fa-angle-double-right"></i>', val: max, hover: activePage < max, cl: 'rounded-r-base' });

    return pages;
  }
}
</script>
