<template>
  <div class="mb-4x">
    <div v-if="utils.objectIsNotEmpty(params)" class="mb-2xl">
      <router-link :to="utils.paramsToString('/search', params)"
        class="border-base border-midGray p-sm hover:bg-blue hover:border-blue hover:text-white transition-all duration-300">
        <i class="fas fa-long-arrow-alt-left mr-xs"></i>
        Back to search results
      </router-link>
    </div>

    <p v-if="loading || !resource" class="mt-2x">
      Loading resource..
    </p>
    <div v-else>
      <div class="mb-xl">
        <search-map
          height="300px"
          :noZoom="true"
          :showInfo="true"
        />
      </div>

      <article class="flex flex-col lg:flex-row">
        <div class="lg:w-2/3 lg:pl-xl lg:order-2 block">

          <h1 class="text-2x mb-md">{{ resource.title && resource.title.trim() ? resource.title : 'No title' }}</h1>

          <div class="whitespace-pre-line mb-2x">
            {{ utils.cleanText(resource.description, true) || 'No description' }}
          </div>

          <div class="mb-2x">
            <div>
              <a :href="`${ apiUrl }/getRecord/${ encodeURIComponent(resource.id) }`" target="_blank" :class="utils.linkClasses()">
                <i class="fas fa-cloud-download-alt mr-xs text-blue"></i> Json
              </a>
            </div>
            <div :class="iconsClass">
              <a :href="`${ apiUrl }/getRecord/${ encodeURIComponent(resource.id) }/xml`" target="_blank" :class="utils.linkClasses()">
                <i class="fas fa-code mr-xs text-blue"></i> Xml
              </a>
            </div>
            <div v-if="utils.validUrl(resource.landingPage)">
              <p :class="iconsClass">
                <span :class="utils.linkClasses()" class="cursor-pointer" v-on:click="toggleCiting">
                  <i class="fas fa-link mr-xs text-blue"></i>
                  Cite
                </span>
                <input v-show="isCiting" ref="citeRef" type="text" :value="citationLink"
                  class="w-full border-base py-sm px-md mt-sm block border-yellow outline-none">
              </p>
              <p :class="iconsClass">
                <a :href="resource.landingPage" target="_blank" class="break-word" :class="utils.linkClasses()">
                  <i class="fas fa-external-link-alt mr-xs text-blue"></i>
                  {{ resource.landingPage }}
                </a>
              </p>
            </div>
          </div>

          <div v-if="resource.collection && resource.collection.hits && resource.collection.hits.length">
            <filtered-items
              :items="resource.collection.hits"
              header="Resource has parts"
              slotType="resource"
            />
            <router-link v-if="resource.collection.total" :to="utils.paramsToString('/search', { q: 'isPartOf:' + resource.id })"
              class="mb-sm block" :class="utils.linkClasses()">
              <i class="fas fa-search mr-xs"></i>
              All parts ({{ resource.collection.total }})
            </router-link>
          </div>

          <filtered-items
            :items="resource.partof"
            header="Resource is part of"
            slotType="resource"
          />

          <filtered-items
            :items="resource.similar"
            header="Thematically similar"
            slotType="resource"
          />

          <h3 class="text-lg font-bold mt-2x mb-md"
            v-if="(Array.isArray(resource.derivedSubject) && resource.derivedSubject.some(d => d.prefLabel)) ||
            (Array.isArray(resource.temporal) && resource.temporal.some(t => t.periodName)) ||
            (Array.isArray(resource.spatial) && resource.spatial.some(s => s.placeName))">
            Tags
          </h3>

          <filtered-items
            :items="resource.derivedSubject"
            slotType="prop"
            filter="prefLabel"
            query="derivedSubject"
            icon="fas fa-tag"
          />

          <filtered-items
            :items="resource.temporal"
            slotType="prop"
            filter="periodName"
            query="temporal"
            icon="far fa-clock"
          />

          <filtered-items
            :items="resource.spatial"
            slotType="prop"
            filter="placeName"
            fields="location"
            icon="fas fa-map-marker-alt"
          />
        </div>

        <div class="lg:w-1/3 lg:pr-xl lg:order-1 mt-2xl lg:mt-none">
          <resource-sidebar />
        </div>
      </article>
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Prop, Watch } from 'vue-property-decorator';
import SearchMap from '../../components/Form/SearchMap.vue';
import utils from '../../utils/utils';
import ResourceSidebar from './ResourceSidebar.vue';
import FilteredItems from './FilteredItems.vue';

@Component({
  components: {
    SearchMap,
    ResourceSidebar,
    FilteredItems
  }
})
export default class Resource extends Vue {
  @Prop() id!: string;
  isCiting: boolean = false;
  utils = utils;

  created () {
    this.initResource(this.id);
  }

  async initResource (id: any) {
    id = String(id);

    if (this.resource?.id && this.resource.id === id) {
      return;
    }

    await this.$store.dispatch('setResource', id);

    this.$nextTick(() => {
      if (this.resource) {
        this.$store.dispatch('setMeta',{
          title: (this.resource.title && this.resource.title.trim()) ? this.resource.title : 'No title',
          description: (this.resource.description || '').slice(0, 155)
        });
      } else {
        this.$router.replace('/404');
      }
    });
  }

  get loading () {
    return this.$store.getters.loading();
  }
  get resource () {
    return this.$store.getters.resource();
  }
  get params () {
    return this.$store.getters.params();
  }
  get assets () {
    return this.$store.getters.assets();
  }

  get apiUrl () {
    // @ts-ignore
    return process.env.apiUrl;
  }
  get iconsClass () {
    return 'mt-md pt-md border-t-base border-gray';
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

  toggleCiting () {
    this.isCiting = !this.isCiting;

    if (this.isCiting) {
      this.$nextTick(() => {
        let citeRef = (this.$refs.citeRef as any);
        citeRef.focus();
        if (typeof citeRef.select === 'function' && !utils.isMobile()) {
          citeRef.select();
        }
      });
    }
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
