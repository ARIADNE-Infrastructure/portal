<template>
	<div>
    <search-map
      class="absolute top-5xl left-0 w-full"
      style="height:calc(100vh - 11rem)"
      :isMultiple="true"
      height="calc(100vh - 11rem)"
      v-on:mapChange="mapChange"
    />

    <div class="absolute top-5xl right-0 z-1001" v-if="!loading">
      <div class="m-base text-lg text-center">
        <div v-if="result.error"
          class="bg-white p-base border-b-base border-darkGray text-red">
          {{ result.error }}
        </div>
        <div class="bg-white p-base">
          <div v-if="data === true">
            <i class="fas fa-circle-notch fa-spin mr-sm backface-hidden"></i>
            Loading..
          </div>
          <div v-else-if="data && data.error" class="text-red">
            Error: Failed to load map data
          </div>
          <div v-else>
            <b>{{ total() }}</b>
            resources in the current section
          </div>
        </div>
        <div class="bg-yellow p-base text-white cursor-pointer hover:bg-green transition-color duration-300"
          v-on:click="toSearch">
          <i class="fas fa-search mr-xs"></i>
          Display as search result
        </div>
      </div>
    </div>

    <div class="fixed top-0 left-0 w-screen h-screen z-neg bg-blue-40"></div>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import SearchMap from '../../components/Form/SearchMap.vue';


@Component({
  components: {
    SearchMap
  }
})
export default class BrowseWhere extends Vue {
  data: any = null;

  created () {
    this.$store.dispatch('setSearch', {
      fromRoute: true
    });
  }

  get loading () {
    return this.$store.getters.loading();
  }
  get result () {
    return this.$store.getters.result();
  }
  get params () {
    return this.$store.getters.params();
  }

  mapChange (data: any) {
    this.data = data;
  }

  toSearch () {
    this.$store.dispatch('setSearch', this?.data?.params || this.params);
  }

  total () {
    let total = this.data ? (this.data?.result?.total) : (this.result?.total);
    let val = total?.value || 0;

    if (val && total?.relation !== 'eq') {
      val += '+';
    }
    return String(val).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }
}
</script>
