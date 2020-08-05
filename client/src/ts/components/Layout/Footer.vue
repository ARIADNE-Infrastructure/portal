<template>
  <footer class="bg-darkGray-40 xl:px-base absolute bottom-0 w-full h-7x z-1002">
    <div class="max-w-screen-xl h-full mx-auto px-base flex items-center">
      <img :src="`${ assets }/eu-flag.png`" alt="eu flag">
      <div class="text-white pl-base">
        <p>
          Copyright &copy; {{ new Date().getFullYear() }} Ariadne. All rights reserved.
          <a href="mailto:webmaster@snd.gu.se?subject=Ariadne-Portal" class="hover:underline block sm:inline mt-xs sm:mt-none">
            <i class="fas fa-envelope"></i> Contact
          </a>
        </p>
        <p class="hidden sm:block">
          Ariadne is funded by the
          <a href="https://ec.europa.eu/research/fp7/index_en.cfm" target="_blank" class="hover:underline">
            European Commission's 7th Framework Programme.
          </a>
        </p>
      </div>
    </div>
  </footer>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';

@Component
export default class LayoutFooter extends Vue {
  get assets(): string {
    return this.$store.getters.assets();
  }
}
</script>
