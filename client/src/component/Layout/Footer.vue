<template>
  <footer class="bg-darkGray-40 xl:px-base absolute bottom-0 w-full h-8x text-mmd">
    <div class="max-w-screen-xl h-full mx-auto px-base flex items-center">
      <img :src="`${ assets }/eu-flag.png`" alt="eu flag" class="hidden md:block">
      <div class="text-white md:pl-base">
        <p class="inline sm:block mb-xs">
          &copy; {{ new Date().getFullYear() }} the ARIADNEplus partners. All rights reserved, visit
          <b-link href="https://ariadne-infrastructure.eu/legal/" target="_blank" class="underline">
            this page
          </b-link>
          for further details.
        </p>
        <p class="inline sm:block mb-xs">
          ARIADNEplus is funded by the European Commission Horizon2020 programme under under Grant Agreement n. 823914.
        </p>

        <b-link
          to="/contact"
          class="hover:underline"
        >
          <i class="fas fa-envelope mr-xs"></i> Contact
        </b-link>

        <span class="pl-xs pr-sm">|</span>

        <b-link
          to="/guide"
          class="hover:underline"
        >
          <i class="fas fa-info-circle text-md mr-xs"></i> ARIADNE portal guide
        </b-link>
      </div>
      <div id="footer-teleport-target"></div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { generalModule } from "@/store/modules";
import BLink from '@/component/Base/Link.vue';

const assets: string = $computed(() => generalModule.getAssetsDir);
</script>
