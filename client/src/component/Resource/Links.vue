<template>
  <div>
    <b-link
      :href="resource.landingPage"
      target="_blank"
      class="block mb-xl py-sm bg-blue hover:bg-blue-80 text-white rounded-base ransition-all duration-300 focus:outline-none text-center"
    >
      <i class="p-sm pl-none fas fa-external-link-alt"></i>
      View resource at provider
    </b-link>

    <div class="md:flex justify-between items-center md:pb-lg mb-md" :class="resource.landingPage ? '' : 'mt-md'">
      <ul class="mb-base md:mb-none md:flex">
        <li class="border-gray border-b-base md:border-b-0 last:border-b-0">
          <b-link
            :href="`${ apiUrl }/getRecord/${ encodeURIComponent(resource.id) }`"
            target="_blank"
            class="block pb-md md:pb-none md:pr-md leading-1 hover:text-black group"
          >
            <i class="fas fa-cloud-download-alt mr-xs text-blue group-hover:text-black"></i>
            Json
          </b-link>
        </li>

        <li class="border-gray border-b-base md:border-b-0 last:border-b-0">
          <b-link
            :href="`${ apiUrl }/getRecord/${ encodeURIComponent(resource.id) }/xml`"
            target="_blank"
            class="block py-md md:py-none md:px-md leading-1 hover:text-black group"
          >
            <i class="fas fa-code mr-xs text-blue group-hover:text-black"></i>
            Xml
          </b-link>
        </li>

        <template v-if="utils.validUrl(resource.landingPage)">
          <li
            class="relative border-gray border-b-base md:border-b-0 leading-1 last:border-b-0"
            @click="toggleCiting"
          >
            <span
              class="block py-md md:py-none md:px-md cursor-pointer hover:text-black group"
            >
              <i class="fas fa-link mr-xs text-blue group-hover:text-black"></i>
              Cite
            </span>

            <div
              class="md:absolute top-xl left-md w-full"
            >
              <input
                v-show="isCiting"
                ref="citeRef"
                type="text"
                :value="citationLink"
                :style="citationStyle"
                class="w-full border-base mb-base py-sm px-md block border-yellow outline-none shadow-bottom"
              >
            </div>
          </li>
        </template>

        <li class="border-gray border-b-base md:border-b-0 last:border-b-0">
          <a class="block py-md md:py-none md:pl-md leading-1 hover:text-black group" href="#"
            v-on:click.prevent="reportIssue">
            <i class="far fa-envelope mr-xs text-blue group-hover:text-black"></i>
            Issue
          </a>
        </li>
      </ul>
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Prop } from 'vue-property-decorator';
import { resourceModule, contactModule } from "@/store/modules";
import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';

@Component({
  components: {
    BLink,
  }
})
export default class ResourceLinks extends Vue {
  @Prop() resourceId!: string;

  isCiting: boolean = false;
  citationWidth: number = 0;
  utils = utils;

  get resource(): any {
    return resourceModule.getResource;
  }

  get citationStyle(): any {
    return {
      width: `${this.citationWidth}px`
    }
  }

  get apiUrl () {
    // @ts-ignore
    return process.env.apiUrl;
  }

  get citationLink () {
    if (this.resource.originalId) {
      const identifiers = ['doi:', 'hdl:', 'urn:', 'http://', 'https://'];
      if (identifiers.some((id: string) => String(this.resource.originalId).startsWith(id))) {
        return this.resource.originalId;
      }
    }

    return this.resource.landingPage;
  }

  reportIssue () {
    contactModule.clearMail();
    contactModule.setSubject('Data quality issue resource #' + this.resourceId);
    this.$router.push('/contact');
  }

  toggleCiting () {
    this.isCiting = !this.isCiting;

    if (this.isCiting) {

      this.$nextTick(() => {
        let citeRef = (this.$refs.citeRef as any);

        // set width
        this.citationWidth = citeRef.scrollWidth + 1;

        // focus
        citeRef.focus();

        if (typeof citeRef.select === 'function' && !utils.isMobile()) {
          citeRef.select();
        }
      });
    }
  }
}
</script>
