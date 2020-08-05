<template>
  <router-link
    to="/"
  >
    <img
      :src="`${ assets }/logo.png`"
      class="transition-opacity duration-300 hover:opacity-80"
      alt="logo"
    >
  </router-link>
</template>

<script lang="ts">
import { Vue, Prop } from 'vue-property-decorator';

export default class HeaderLogo extends Vue {
  public get assets(): string {
    return this.$store.getters.assets();
  }
}
</script>
