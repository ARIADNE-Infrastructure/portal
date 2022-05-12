<!--
  App.vue - main component
-->
<template>
  <div>
    <div>
      <layout-mobile-header />
      <layout-desktop-header />

      <div class="pt-4x pb-11x">
        <router-view />
      </div>

      <layout-footer />
    </div>

    <help-loader />
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { generalModule } from "@/store/modules";
import LayoutMobileHeader from './component/Layout/MobileHeader.vue';
import LayoutDesktopHeader from './component/Layout/DesktopHeader.vue';
import LayoutFooter from './component/Layout/Footer.vue';
import HelpLoader from './component/Help/Loader.vue';
import '../static/fa/css/all.min.css';

onMounted(() => {
  generalModule.setWindow();
  window.addEventListener('resize', () => generalModule.setWindow());
});
</script>
