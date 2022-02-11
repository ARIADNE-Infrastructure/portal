<template>
  <div class="md:hidden">
    <!-- overlay -->
    <div
      v-if="show"
      class="bg-black opacity-60 absolute w-full h-full z-1005"
      @click="toggle"
    >
    </div>

    <header
      class="fixed top-0 w-full bg-lightGray z-1005"
      :class="{ 'border-b-base border-gray shadow-bottomDark': !show }"
    >
      <div class="flex items-center h-4x">
        <!-- logo -->
        <span
          @click="navigate('/')"
          class="block p-base cursor-pointer"
        >
          <img
            :src="`${ assets }/logo.png`"
            class="transition-opacity duration-300 hover:opacity-80 backface-hidden"
            alt="logo"
          >
        </span>

        <!-- stacked menu toggle -->
        <div class="flex w-full justify-end h-full">
          <button
            type="button"
            class="p-base mt-xs mr-base focus:outline-none transition-all duration-300 hover:outline-none hover:text-yellow"
            @click="toggle"
          >
            <i v-if="show" class="fas fa-times text-xl"></i>
            <i v-else class="fa-bars fas text-xl" />
          </button>
        </div>
      </div>

      <!-- menu -->
      <transition
        v-on:before-enter="onLeave" v-on:enter="onEnter"
        v-on:before-leave="onEnter" v-on:leave="onLeave"
      >
        <div
          v-show="show"
          class="ease-out duration-200 overflow-y-hidden"
        >
          <div class="z-1005">
            <filter-search
              class="my-lg px-base"
              color="yellow"
              hoverStyle="hover:bg-yellow-80"
              focusStyle="focus:border-yellow"
              :big="true"
              :useCurrentSearch="false"
              @submit="onSubmit"
            />
            <ul>
              <li
                v-for="item in generalModule.getMainNavigation"
                :key="item.path"
                class="bg-lightGray text-mmd"
              >
                <span
                  @click="navigate(item.path)"
                  :class="`${ item.hover } ${ item.border }` + (isActive(item.path) ? ` ${ item.bg } ${ item.border }` : '')"
                  class="transition-all duration-300 text-midGray2 border-b-3 block p-base text-center cursor-pointer"
                >
                  <i
                    :class="`fa-${ item.icon }`"
                    class="fas mr-xs"
                  />

                  {{ item.name }}
                </span>
              </li>
            </ul>
          </div>
        </div>
      </transition>
    </header>
  </div>
</template>

<script lang="ts">
import { Component, Mixins, Watch } from 'vue-property-decorator';
import { generalModule } from "@/store/modules";
import utils from '@/utils/utils';

import ListAccordionMixin from '@/component/List/AccordionMixin.vue';
import FilterSearch from '@/component/Filter/Search.vue';

@Component({
  components: {
    FilterSearch,
  }
})
export default class LayoutMobileHeader extends Mixins(ListAccordionMixin) {
  generalModule = generalModule;
  path: string = '';

  mounted () {
    this.updateMenuPath();
  }

  get assets(): string  {
    return generalModule.getAssetsDir;
  }

  navigate (path: string) {
    this.$router.push(path);
    this.show = false;
  }

  onSubmit () {
    this.show = false
    utils.blurMobile();
  }

  onEnter(el: any): void {
    document.body.classList.add('overflow-y-hidden');
    this.enter(el);
  }

  onLeave(el: any): void {
    document.body.classList.remove('overflow-y-hidden');
    this.leave(el);
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
