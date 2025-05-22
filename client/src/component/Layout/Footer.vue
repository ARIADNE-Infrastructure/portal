<template>
  <footer class="bg-darkGray-40 xl:px-base absolute bottom-0 w-full h-8x text-mmd">
    <div class="h-full mx-auto px-base flex items-center justify-center text-center">
      <img :src="`${ assets }/eu-flag.png`" alt="eu flag" class="hidden md:block">
      <div class="text-white md:pl-base">
        <p class="inline sm:block mb-sm">
          &copy; {{ new Date().getFullYear() }} the ARIADNEplus partners &amp; ARIADNE RI AISBL members. All rights reserved.
          <b-link href="https://www.ariadne-research-infrastructure.eu/legal/" target="_blank" class="underline">Details</b-link>.
        </p>

        <p class="inline sm:block mb-sm">
          ARIADNEplus was funded by the European Commission Horizon2020 programme under under Grant Agreement n. 823914.
        </p>

        <div class="inline xxs:block">
          <b-link to="/contact" class="hover:underline">
            <i class="fas fa-envelope mr-xs"></i> Contact
          </b-link>
          <span class="pl-xs pr-sm">|</span>
          <b-link href="https://ariadne-infrastructure.github.io/docs/Portal_User_Guide/Section-1_What" target="_blank" class="hover:underline">
            <i class="fas fa-file-pdf text-md mr-xs"></i> ARIADNE portal guide
          </b-link>
          <span class="pl-xs pr-sm">|</span>
          <b-link to="/about" class="hover:underline">
            <i class="fas fa-info-circle text-md mr-xs"></i> Disclaimer
          </b-link>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { generalModule } from "@/store/modules";
import BLink from '@/component/Base/Link.vue';

const assets: string = $computed(() => generalModule.getAssetsDir);
</script>
