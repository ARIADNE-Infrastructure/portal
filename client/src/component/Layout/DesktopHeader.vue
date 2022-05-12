<template>
  <header
    class="hidden md:block fixed top-0 z-30 top-0 bg-blue w-full h-4x bg-lightGray shadow-bottomDark"
  >
    <div class="flex relative items-center max-w-screen-xl mx-auto h-4x">
      <!-- logo -->
      <b-link
        to="/"
        class="ml-base flex transition-opacity duration-300 hover:opacity-80"
        style="margin-bottom: -28px">
        <img
          :src="`${ assets }/logo.png`"
          class="backface-hidden"
          alt="Ariadne logo"
        />
        <p class="text-red text-2x" style="font-family: 'PT Sans'; white-space: nowrap; margin-top: -7px">
          ARIADNE PORTAL
        </p>
      </b-link>

      <div class="w-1/2 border-b-3 border-yellow ml-5x absolute bottom-0"></div>

      <!--  menu -->
      <ul class="flex flex-1 h-full justify-end ml-2x xl:mr-base">
        <li
          v-for="item in generalModule.getMainNavigation"
          :key="item.path"
          class="h-full flex-1 bg-lightGray relative group"
          style="max-width: 200px"
        >
          <b-link
            :to="item.path"
            :class="`${ item.groupHover || '' } ${ item.hover } ${ item.border }` + (isActive(item.path) ? ` ${ item.bg } ${ item.border }` : '')"
            class="block relative transition-all duration-300 text-mmd text-midGray2 border-b-3 flex items-center justify-center h-full"
          >
            <span class="leading-1">
              <i
                :class="`fa-${ item.icon }`"
                class="fas mr-sm"
              />
              {{ item.name }}
            </span>
          </b-link>

          <div v-if="item.subMenu" class="absolute w-full bg-lightGray opacity-0 z-neg10 group-hover:opacity-100 group-hover:z-30 invisible group-hover:visible transition-all duration-300">
            <b-link v-for="sub in item.subMenu" :key="sub.path" :to="sub.path"
              :class="`${ sub.hover } ${ sub.border }` + (isActive(sub.path) ? ` ${ sub.bg } ${ sub.border }` : '')"
              class="block relative py-lg transition-all duration-300 text-mmd text-midGray2 border-b-3 flex items-center justify-center h-full">
              <span class="leading-1">
                <i :class="`fa-${ sub.icon }`" class="fas mr-sm" />
                {{ sub.name }}
              </span>
            </b-link>
          </div>
        </li>
      </ul>
    </div>
  </header>
</template>

<script setup lang="ts">
import { watch, onMounted } from 'vue';
import { $ref, $computed } from 'vue/macros';
import { useRoute } from 'vue-router'
import { generalModule } from "@/store/modules";
import BLink from '@/component/Base/Link.vue';

const route = useRoute();
let path: string = $ref('');

const assets: string = $computed(() => generalModule.getAssetsDir);

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
