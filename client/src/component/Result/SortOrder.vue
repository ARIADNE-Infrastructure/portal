<!-- Front page -->
<template>
  <div class="flex items-center">
    <h3 class="font-bold mr-md text-mmd">
      Order
    </h3>

    <b-select
      :value="order"
      :options="options"
      color="blue"
      @input="setOrder"
    />
  </div>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import { search } from "@/store/modules";
import BSelect from '@/component/Base/Select.vue';
import utils from '@/utils/utils';

@Component({
  components: {
    BSelect
  }
})
export default class ResultSortOrder extends Vue {
  get params(): any {
    return search.getParams;
  }

  get options(): any[] {
    return search.getSortOptions;
  }

  get order() {
    if (this.params.sort && this.params.order) {
      return `${ this.params.sort }-${ this.params.order }`;
    }

    let paramsAmount = Object.keys(this.params).length;
    if (!this.params.q && (paramsAmount < 2 || (paramsAmount === 2 && Number.isFinite(this.params.page)))) {
      return 'issued-desc';
    }
    return '_score-desc';
  }

  setOrder(orderStr: string) {
    let arr = orderStr.split('-');

    search.setSearch({
      sort: arr[0],
      order: arr[1],
    });
  }
}
</script>
