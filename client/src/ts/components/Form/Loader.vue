<template>
  <div class="fixed top-0 left-0 w-screen h-screen transition-all duration-300"
    :class="loading ? 'z-1004 opacity-100' : 'z-neg opacity-0'">
    <div class="mt-4x text-center py-lg bg-yellow-80 text-white">
      <i class="fas fa-spinner fa-spin mr-xs"></i>
      Loading..
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Prop } from 'vue-property-decorator';

@Component
export default class Loader extends Vue {
  get loading () {
    return this.$store.getters.loading();
  }
}
</script>
