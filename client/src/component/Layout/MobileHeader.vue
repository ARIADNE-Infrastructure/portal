<template>
  <div class="md:hidden">
    <!-- overlay -->
    <div
      v-if="show"
      class="bg-black opacity-60 absolute w-full h-full z-30"
      @click="toggle"
    >
    </div>

    <header
      class="fixed top-0 w-full bg-lightGray z-30"
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
          <div class="z-30">
            <ul class="border-t-2 border-midGray">
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
                    class="fas mr-sm"
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

<script setup lang="ts">
import { watch, onMounted } from 'vue';
import { $ref, $computed, $$ } from 'vue/macros';
import { useRoute, useRouter } from 'vue-router'
import { generalModule } from "@/store/modules";

const router = useRouter();
const route = useRoute();
let show: boolean = $ref(false);
let path: string = $ref('');

const assets: string = $computed(() => generalModule.getAssetsDir);

const toggle = () => {
  show = !show;
}

const enter = (el: HTMLElement) => {
  el.style.height = el.scrollHeight + 'px';
}

const leave = (el: HTMLElement): void => {
  el.style.height = '0px';
}

const navigate = (path: string) => {
  router.push(path);
  show = false;
}

const onEnter = (el: HTMLElement) => {
  // lock scroll behind overlay
  document.body.classList.add('overflow-y-hidden');
  enter(el);
}

const onLeave = (el: HTMLElement) => {
  // unlock scroll behind overlay
  document.body.classList.remove('overflow-y-hidden');
  leave(el);
}

const isActive = (itemPath: string): boolean => {
  return path.includes(itemPath) ||
    (itemPath.includes('search') && path.includes('resource'));
}

const updateMenuPath = (): void => {
  path = route.fullPath;
}

onMounted(updateMenuPath);
watch(route, updateMenuPath);
</script>
