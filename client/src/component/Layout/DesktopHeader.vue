<template>
  <header
    class="hidden md:block fixed top-0 z-1005 top-0 bg-blue w-full h-4x bg-lightGray shadow-bottomDark"
  >
    <div class="flex relative items-center max-w-screen-xl mx-auto h-4x">
    <template>
      <!-- logo -->
      <b-link
        to="/"
        class="absolute -bottom-base left-base"
      >
        <img
          :src="`${ assets }/logo.png`"
          class="transition-opacity duration-300 hover:opacity-80 backface-hidden"
          alt="logo"
        >
      </b-link>

      <!-- search -->
      <div class="flex max-w-lg flex-1 ml-5x border-yellow border-b-3 items-center h-full p-base">
        <filter-search
          color="yellow"
          hoverStyle="hover:bg-yellow-80"
          focusStyle="focus:border-yellow"
          :useCurrentSearch="false"
        />
      </div>
    </template>

    <!--  menu -->
    <div class="flex flex-1 h-full xl:pr-base">
      <ul class="flex w-full">
        <li
          v-for="item in generalModule.getMainNavigation"
          :key="item.path"
          class="h-full flex-1 bg-lightGray"
        >
          <b-link
            :to="item.path"
            :class="`${ item.hover } ${ item.border }` + (isActive(item.path) ? ` ${ item.bg } ${ item.border }` : '')"
            class="block relative transition-all duration-300 text-mmd text-midGray2 border-b-3 flex items-center justify-center h-full"
          >
            <span class="leading-1">
              <i
                :class="`fa-${ item.icon }`"
                class="fas mr-xs"
              />
              {{ item.name }}
            </span>
          </b-link>
        </li>
      </ul>
      </div>
    </div>
  </header>
</template>

<script lang="ts">
import { Component, Vue, Watch } from 'vue-property-decorator';
import { generalModule } from "@/store/modules";
import BLink from '@/component/Base/Link.vue';
import FilterSearch from '@/component/Filter/Search.vue';

@Component({
  components: {
    BLink,
    FilterSearch,
  }
})
export default class LayoutDesktopHeader extends Vue {
  generalModule = generalModule;
  path: string = '';

  mounted () {
    this.updateMenuPath();
  }

  get assets(): string {
    return generalModule.getAssetsDir;
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
