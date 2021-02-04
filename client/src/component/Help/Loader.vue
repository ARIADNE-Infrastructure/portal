<template>
  <div
    class="fixed top-0 left-0 w-full h-full transition-opacity duration-300 cursor-default flex justify-center items-center"
    :class="general.getLoading ? 'z-max opacity-100' : 'z-neg opacity-0'"
  >
    <div>
      <i class="fas fa-spinner fa-spin mr-xs text-2x text-darkGray"></i>
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Prop } from 'vue-property-decorator';
import { general } from "@/store/modules";

@Component
export default class HelpLoader extends Vue {
  general = general;
}
</script>
