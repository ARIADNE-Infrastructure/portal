<template>
  <div>
    <div
      v-if="!forced && (isLoading || !resource)"
      class="py-3x px-base mx-auto max-w-screen-xl"
    >
      <h1 class="text-lg text-center text-midGray">
        Loading resource..
      </h1>
    </div>

    <div v-else class="text-mmd">
      <div v-if="utils.objectIsNotEmpty(params)">
        <b-link
          :to="utils.paramsToString('/search', params)"
          class="p-md bg-black-80 text-white border-b-base border-black hover:bg-blue transition-bg duration-300 w-full block text-center"
        >
          <i class="fas fa-long-arrow-alt-left mr-xs"></i>
          Back to search results
        </b-link>
      </div>

      <div class="mb-2x">
        <resource-map
          v-if="resource"
          class="map--default"
        />
      </div>

      <article class="px-base mx-auto max-w-screen-xl lg:flex">
        <div class="w-full lg:w-2/3 lg:pr-2x">
          <resource-title />
          <resource-description class="mt-2x pt-xs mb-3x lg:mb-2x" />
          <resource-links class="mt-lg block lg:hidden" :resourceId="id" />
          <resource-main class="mt-lg" />
        </div>

        <div class="w-full lg:w-1/3 pt-sm lg:pl-2x lg:border-l-base border-gray">
          <resource-links class="hidden lg:block" :resourceId="id" />
          <resource-sidebar :initResource="initResource" />
        </div>
      </article>
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Prop, Watch } from 'vue-property-decorator';
import { searchModule, generalModule, resourceModule } from "@/store/modules";
import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';

// unique
import ResourceMap from './Resource/Map.vue';
import ResourceTitle from './Resource/Title.vue';
import ResourceDescription from './Resource/Description.vue';
import ResourceLinks from './Resource/Links.vue';
import ResourceMain from './Resource/Main.vue';
import ResourceSidebar from './Resource/Sidebar.vue';

@Component({
  components: {
    BLink,
    ResourceMap,
    ResourceTitle,
    ResourceDescription,
    ResourceLinks,
    ResourceMain,
    ResourceSidebar,
  },
})
export default class Resource extends Vue {
  @Prop() id!: string;
  forced: boolean = false;
  utils = utils;

  created () {
    this.initResource(this.id);
  }

  async initResource (id: any, force: boolean = false) {

    id = String(id);
    this.forced = force;

    if (this.resource?.id && this.resource.id === id && !force) {
      return;
    }

    await resourceModule.setResource(id);

    this.$nextTick(() => {
      if (this.resource) {
        // Set document metadata

        generalModule.setMeta({
          title: (this.resource.title?.text && this.resource.title?.text.trim()) ? this.resource.title?.text : 'No title',
          description: this.resource?.description?.text ? this.resource?.description?.text.slice(0, 155) : ''
        });
      } else {
        this.$router.replace('/404');
      }
    });

  }

  get isLoading(): boolean {
    return generalModule.getIsLoading;
  }

  get resource(): any {
    return resourceModule.getResource;
  }

  get params(): any {
    return searchModule.getParams;
  }

  @Watch('$route')
  async onRouteUpdate (to: any) {
    if (to?.params?.id) {
      await this.initResource(to.params.id);
      this.$nextTick(() => this.$forceUpdate());
    }
  }
}
</script>