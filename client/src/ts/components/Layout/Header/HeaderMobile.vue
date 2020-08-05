<template>
  <div class="md:hidden">
    <!-- overlay -->
    <div
      v-if="show"
      class="bg-black opacity-60 absolute w-full h-full z-1003"
      @click="toggle"
    >
    </div>

    <header
      class="fixed top-0 w-full bg-lightGray z-1003"
      :class="{ 'border-b-base border-gray shadow-bottom': !show }"
    >
      <div class="flex items-center h-4x">
        <!-- logo -->
        <header-logo class="block p-base" />

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
          <div class="z-1003">
            <search
              class="my-md px-base"
              color="yellow"
              bg="bg-yellow"
              hover="hover:bg-yellow-80"
              focus="focus:border-yellow"
              :big="true"
              :useCurrentSearch="false"
            />
            <ul>
              <li
                v-for="item in items"
                :key="item.path"
                class="bg-lightGray"
              >
                <router-link
                  :to="item.path"
                  :class="`${ item.hover } ${ item.border }`"
                  class="transition-all duration-300 text-midGray2 border-b-3 block p-base text-center"
                >
                  <i
                    :class="`fa-${ item.icon }`"
                    class="fas mr-xs"
                  />

                  {{ item.name }}
                </router-link>
              </li>
            </ul>
          </div>
        </div>
      </transition>
    </header>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Mixins } from 'vue-property-decorator';
import AccordionMixin from '../../Form/AccordionMixin.vue';
import { MainMenuItem } from '../../../utils/interface';
import Search from '../../Form/Search.vue';
import HeaderLogo from './Logo.vue';

@Component({
  components: {
    HeaderLogo,
    Search,
  }
})
export default class HeaderMobile extends Mixins(AccordionMixin) {
  get items(): MainMenuItem[] {
    return this.$store.getters.mainNavigation();
  }

  onEnter(el: any): void {
    document.body.classList.add('overflow-y-hidden');
    this.enter(el);
  }

  onLeave(el: any): void {
    document.body.classList.remove('overflow-y-hidden');
    this.leave(el);
  }
}
</script>
