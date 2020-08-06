<template>
  <header
    class="hidden md:block fixed top-0 z-1003 top-0 bg-blue w-full h-4x bg-lightGray"
  >
    <div class="flex relative items-center max-w-screen-xl mx-auto h-4x">
    <template>
      <!-- logo -->
      <router-link
        to="/"
        class="absolute -bottom-base left-base"
      >
        <img
          :src="`${ assets }/logo.png`"
          class="transition-opacity duration-300 hover:opacity-80"
          alt="logo"
        >
      </router-link>

      <!-- search -->
      <div class="flex max-w-lg flex-1 ml-5x border-yellow border-b-3 items-center h-full p-base">
        <search
          color="yellow"
          bg="bg-yellow"
          hover="hover:bg-yellow-80"
          focus="focus:border-yellow"
          :useCurrentSearch="false"
        />
      </div>
    </template>

    <!--  menu -->
    <div class="flex flex-1 h-full xl:pr-base">
      <ul class="flex w-full">
        <li
          v-for="item in items"
          :key="item.path"
          class="h-full flex-1 bg-lightGray"
        >
          <router-link
            :to="item.path"
            :class="`${ item.hover } ${ item.border }` + (isActive(item.path) ? ` ${ item.bg } ${ item.border }` : '')"
            class="block relative transition-all duration-300 text-midGray2 border-b-3 flex items-center justify-center h-full"
          >
            <span class="leading-none">
              <i
                :class="`fa-${ item.icon }`"
                class="fas mr-xs"
              />
              {{ item.name }}
            </span>
          </router-link>
        </li>
      </ul>
      </div>
    </div>
  </header>
</template>

<script lang="ts">
import { Component, Vue, Prop, Watch } from 'vue-property-decorator';
import Search from '../../Form/Search.vue';
import { MainMenuItem } from '../../../utils/interface';

@Component({
  components: {
    Search,
  }
})
export default class HeaderDesktop extends Vue {
  path: string = '';

  mounted () {
    this.updateMenuPath();
  }

  get items(): MainMenuItem[] {
    return this.$store.getters.mainNavigation();
  }
  get assets(): string {
    return this.$store.getters.assets();
  }

  isActive (path: string): boolean {
    return this.path.includes(path) ||
      (path.includes('search') && this.path.includes('resource'));
  }

  @Watch('$route')
  updateMenuPath () {
    this.path = this.$router.currentRoute.path;
  }
}
</script>
