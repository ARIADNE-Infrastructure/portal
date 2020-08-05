<!--
  App.vue - main component
-->
<template>
	<div>
    <div>
      <header-mobile />
      <header-desktop />

      <div class="pt-7x pb-8x px-base mx-auto max-w-screen-xl">
        <router-view />
      </div>

      <layout-footer />
    </div>

    <loader />
  </div>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import HeaderMobile from './components/Layout/Header/HeaderMobile.vue';
import HeaderDesktop from './components/Layout/Header/HeaderDesktop.vue';
import LayoutFooter from './components/Layout/Footer.vue';
import Loader from './components/Form/Loader.vue';

@Component({
  components: {
    HeaderMobile,
    HeaderDesktop,
    LayoutFooter,
    Loader,
  }
})
export default class App extends Vue {
}
</script>
