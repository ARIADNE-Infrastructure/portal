<template>
  <div>

    <h3 class="text-lg font-bold mb-lg">
      <i class="fas fa-link mr-sm"></i>
      Resource links
    </h3>

    <b-link
      v-if="resource.landingPage"
      class="block pb-md md:pb-none md:pr-md leading-1 hover:text-black group mb-lg"
      :useDefaultStyle="true"
      target="_blank"
      :href="resource.landingPage"
    >
      <i class="fas fa-external-link-alt mr-sm text-blue group-hover:text-black"></i>View resource at provider
    </b-link>

    <div class="md:flex justify-between items-center md:pb-lg mb-md" :class="resource.landingPage ? '' : 'mt-md'">
      <ul class="mb-base md:mb-none md:flex">
        <li class="border-gray border-b-base md:border-b-0 last:border-b-0">
          <b-link
            :href="`${ apiUrl }/getRecord/${ encodeURIComponent(resource.id) }`"
            target="_blank"
            class="block pb-md md:pb-none md:pr-md leading-1 hover:text-black group"
          >
            <i class="fas fa-cloud-download-alt mr-sm text-blue group-hover:text-black"></i>
            Json
          </b-link>
        </li>

        <li class="border-gray border-b-base md:border-b-0 last:border-b-0">
          <b-link
            :href="`${ apiUrl }/getRecord/${ encodeURIComponent(resource.id) }/xml`"
            target="_blank"
            class="block py-md md:py-none md:px-md leading-1 hover:text-black group"
          >
            <i class="fas fa-code mr-sm text-blue group-hover:text-black"></i>
            Xml
          </b-link>
        </li>

        <li class="border-gray border-b-base md:border-b-0 last:border-b-0">
          <b-link
            :href="resource.identifier"
            target="_blank"
            class="block py-md md:py-none md:px-md leading-1 hover:text-black group"
          >
            <i class="fas fa-share-alt mr-sm text-blue group-hover:text-black"></i>
            Rdf
          </b-link>
        </li>
        <template v-if="utils.validUrl(resource.landingPage)">
          <li
            class="relative border-gray border-b-base md:border-b-0 leading-1 last:border-b-0 transition-all duration-300"
            :class="{ 'text-green': isCiting }"
            @click="toggleCiting"
          >
            <span
              class="block py-md md:py-none md:px-md cursor-pointer hover:text-black group"
            >
              <i class="fas fa-link mr-sm text-blue group-hover:text-black"></i>
              Cite
            </span>
          </li>
        </template>

        <li class="border-gray border-b-base md:border-b-0 last:border-b-0">
          <a class="block py-md md:py-none md:pl-md leading-1 hover:text-black group" href="#"
            v-on:click.prevent="reportIssue">
            <i class="far fa-envelope mr-sm text-blue group-hover:text-black"></i>
            Report an issue
          </a>
        </li>
      </ul>
    </div>
    <div v-show="isCiting">
      <input
        v-on:focus="toggleCiting(true)"
        :id="citeRefId"
        type="text"
        :value="citationLink"
        style="width:100%"
        class="w-full border-base py-sm px-md block border-yellow outline-none shadow-bottom"
      >
    </div>
  </div>
</template>

<script setup lang="ts">
import { nextTick } from 'vue';
import { $ref, $computed } from 'vue/macros';
import { useRouter } from 'vue-router'
import { resourceModule, contactModule } from "@/store/modules";
import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';
import ResourceFilteredItems from './FilteredItems.vue';

const props = defineProps<{
  resourceId: string,
}>();

const router = useRouter();
const citeRefId: string = 'citeRef-' + utils.getUniqueId();
const resource = $computed(() => resourceModule.getResource);
const apiUrl: string | undefined = $computed(() => process.env.apiUrl);
let isCiting: boolean = $ref(false);

const citationLink: string = $computed(() => {
  if (resource.originalId) {
    const identifiers = ['doi:', 'hdl:', 'urn:', 'http://', 'https://'];
    if (identifiers.some((id: string) => String(resource.originalId).startsWith(id))) {
      return resource.originalId;
    }
  }
  return resource.landingPage;
});

const reportIssue = () => {
  contactModule.clearMail();
  contactModule.setSubject('Data quality issue resource #' + props.resourceId);
  router.push('/contact');
};

const toggleCiting = (val?: boolean) => {
  isCiting = typeof val === 'boolean' ? val : !isCiting;
  if (isCiting) {
    nextTick(() => {
      const citeDiv = (document.getElementById(citeRefId) as any);
      citeDiv.focus();
      if (typeof citeDiv.select === 'function' && !utils.isMobile()) {
        citeDiv.select();
      }
    });
  }
};
</script>
