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
import { searchModule } from "@/store/modules";
import BSelect from '@/component/Base/Select.vue';

@Component({
  components: {
    BSelect
  }
})
export default class ResultSortOrder extends Vue {
  get params(): any {
    return searchModule.getParams;
  }

  get options(): any[] {
    return searchModule.getSortOptions;
  }

  get order(): string {
    return `${ this.params.sort }-${ this.params.order }`;
  }

  setOrder(orderStr: string): void {
    let arr = orderStr.split('-');

    searchModule.setSearch({
      sort: arr[0],
      order: arr[1],
    });
  }
}
</script>
