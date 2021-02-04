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

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import LayoutMobileHeader from './component/Layout/MobileHeader.vue';
import LayoutDesktopHeader from './component/Layout/DesktopHeader.vue';
import LayoutFooter from './component/Layout/Footer.vue';
import HelpLoader from './component/Help/Loader.vue';
import '../static/fa/css/all.min.css';

@Component({
  components: {
    LayoutMobileHeader,
    LayoutDesktopHeader,
    LayoutFooter,
    HelpLoader,
  }
})
export default class App extends Vue {
}
</script>
