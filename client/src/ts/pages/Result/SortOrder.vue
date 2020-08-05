<!-- Front page -->
<template>
  <div class="flex items-center">
    <h3 class="font-bold mr-md">
      Order
    </h3>

    <base-select
      :value="order"
      :options="options"
      color="blue"
      @input="setOrder"
    />
  </div>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import BaseSelect from '../../components/Form/BaseSelect.vue';

@Component({
  components: {
    BaseSelect
  }
})
export default class SortOrder extends Vue {
  get params() {
    return this.$store.getters.params();
  }
  get options() {
    return this.$store.getters.sortOptions();
  }

  get order() {
    if (this.params.sort && this.params.order) {
      return `${ this.params.sort }-${ this.params.order }`;
    }
    return '';
  }

  setOrder(orderStr: string) {
    let arr = orderStr.split('-');

    this.$store.dispatch('setSearch', {
      sort: arr[0],
      order: arr[1],
    });
  }
}
</script>
