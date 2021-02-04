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
          v-if="resource && resource.resourceType !== 'collection'"
          class="map--default"
        />
      </div>

      <article class="px-base mx-auto max-w-screen-xl lg:flex">
        <div class="w-100 lg:w-2/3 lg:pr-2x">
          <h1 class="text-2xl mb-md">
            {{ resource.title && resource.title.trim() ? resource.title : 'No title' }}
          </h1>

          <!-- No whitespace between tags here -->
          <div class="whitespace-pre-line mb-3x lg:mb-2x">{{ utils.cleanText(resource.description, true) || 'No description' }}</div>

          <resource-links class="block lg:hidden" :resourceId="id" />
          <resource-main />
        </div>

        <div class="w-100 lg:w-1/3 pt-sm lg:pl-2x lg:border-l-base border-gray">
          <resource-links class="hidden lg:block" :resourceId="id" />
          <resource-sidebar :initResource="initResource" />
        </div>
      </article>
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Prop, Watch } from 'vue-property-decorator';
import { search, general, resource } from "@/store/modules";
import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';

// unique
import ResourceMap from './Resource/Map.vue';
import ResourceLinks from './Resource/Links.vue';
import ResourceMain from './Resource/Main.vue';
import ResourceSidebar from './Resource/Sidebar.vue';

@Component({
  components: {
    BLink,
    ResourceMap,
    ResourceLinks,
    ResourceMain,
    ResourceSidebar,
  }
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

    await resource.setResource(id);

    this.$nextTick(() => {
      if (this.resource) {
        general.setMeta({
          title: (this.resource.title && this.resource.title.trim()) ? this.resource.title : 'No title',
          description: (this.resource.description || '').slice(0, 155)
        });
      } else {
        this.$router.replace('/404');
      }
    });
  }

  get isLoading(): boolean {
    return general.getLoading;
  }

  get resource(): any {
    return resource.getResource;
  }

  get params(): any {
    return search.getParams;
  }

  get assets(): string {
    return general.getAssetsDir;
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
