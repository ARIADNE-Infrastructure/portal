<template>
	<div class="overflow-x-hidden flex flex-col lg:flex-row">
    <!-- sidebar -->
    <aside class="lg:w-1/3 lg:pr-lg block order-last lg:order-first">
      <result-sidebar />
    </aside>

    <!-- main content -->
    <section class="lg:w-2/3 lg:pl-lg block">
      <info class="py-base pt-none" />

      <div class="lg:hidden">
        <search
          color="blue"
          bg="bg-blue"
          hover="hover:bg-blue-80"
          focus="focus:border-blue"
          class="mt-sm mb-xl"
          :big="true"
          :useCurrentSearch="true"
          :showFields="true"
          v-on:submit="utils.blurMobile()"
        />
      </div>

      <div class="md:flex mt-sm items-center justify-between pt-none">
        <paginator />
        <sort-order class="mt-lg md:mt-none" />
      </div>

      <div class="mb-base mt-xl">
        <p v-if="result.error" class="mt-xl text-red">
          {{ result.error }}
        </p>
        <p v-else-if="!utils.objectIsNotEmpty(result) && loading" class="mt-xl">
          Searching..
        </p>
        <p v-else-if="!result.hits || !result.hits.length" class="mt-xl">
          No results found
        </p>
        <div v-else>
          <router-link
            v-for="(res, key) in result.hits"
            v-bind:key="key"
            :to="`/resource/${ res.id }`"
            class="block border-t-base border-gray p-base transition-all duration-300 hover:bg-lightGray-60 link-trigger"
          >
            <div class="float-left" style="width:40px;margin-right:15px;">
              <img alt="icon" :src="`${ assets }/result/icon-${ getImgId(res.data) }.png`"
                width="40" height="40">
            </div>
            <div class="float-left" style="width:calc(100% - 55px)">
              <h3 class="text-blue font-bold mb-base" v-html="getMarked(res.data.title) || 'No title'"></h3>
              <div v-if="!!res.data.description" class="mb-base">
                <p v-html="getMarked(utils.cleanText(res.data.description, false).slice(0, 400)) + '..'"></p>
              </div>
              <div>
                <div v-for="(agg, key) in getAggregations(res.data)" :key="key">
                  <div v-if="agg.data">
                    <b>{{ aggTitles[agg.id] || utils.sentenceCase(agg.id) }}</b>:
                    <span v-html="agg.data"></span>
                  </div>
                </div>
                <div v-if="res.data.issued">
                  <b>Issued</b>: {{ utils.formatDate(res.data.issued) }}
                </div>
                <div v-if="res.data.modified">
                  <b>Modified</b>: {{ utils.formatDate(res.data.modified) }}
                </div>
              </div>
            </div>
            <div class="clearfix"></div>
          </router-link>
        </div>
      </div>

      <paginator class="py-base" :scrollTop="true" />
    </section>

    <div class="clearfix mb-4x"></div>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Watch } from 'vue-property-decorator';
import utils from '../../utils/utils';
import ResultSidebar from './ResultSidebar.vue';
import Info from './Info.vue';
import Paginator from './Paginator.vue';
import SortOrder from './SortOrder.vue';
import Search from '../../components/Form/Search.vue';

@Component({
  components: {
    ResultSidebar,
    Info,
    Paginator,
    SortOrder,
    Search,
  }
})
export default class Result extends Vue {
  utils = utils

  async mounted () {
    await this.onRouteUpdate();
  }

  get params () {
    return this.$store.getters.params();
  }
  get result () {
    return this.$store.getters.result();
  }
  get assets () {
    return this.$store.getters.assets();
  }
  get loading () {
    return this.$store.getters.loading();
  }
  get aggTitles () {
    return this.$store.getters.aggTitles();
  }

  setMeta () {
    let title = 'Search';

    if (this.params.q) {
      title = `Search results: ${ this.params.q }`;
    }
    if (parseInt(this.params.page) > 1) {
      title += ` (page ${ this.params.page })`;
    }

    this.$store.dispatch('setMeta', {
      title: title,
      description: title,
    });
  }

  getImgId (item: any): number {
    let id = parseInt(item?.archaeologicalResourceType?.id);
    if (Number.isFinite(id) && id >= 1 && id <= 6) {
      return id;
    }
    return 2;
  }

  getAggregations (data: any): Array<any> {
    let q: string = (this.params.q || '').trim();
    let aggs: any = [
      { id: 'archaeologicalResourceType', prop: 'name', always: true },
      { id: 'spatial', prop: 'placeName', always: true },
      { id: 'publisher', prop: 'name', always: true },
      { id: 'nativeSubject', prop: 'prefLabel', sentence: true, always: true },
      { id: 'derivedSubject', prop: 'prefLabel', sentence: true },
      { id: 'keyword', sentence: true },
      { id: 'contributor', prop: 'name' },
      { id: 'temporal', prop: 'periodName', sentence: true },
      { id: 'aatSubjects', param: 'subjectLabel', prop: 'label', sentence: true }
    ];

    for (let i = 0; i < aggs.length; i++) {
      let agg = aggs[i],
          d = data[agg.id];

      if (d) {
        if (agg.id === 'archaeologicalResourceType') {
          d = [d];
        }
        if (Array.isArray(d) && d.length) {
          if (!agg.prop || d.some((p: any) => p[agg.prop])) {
            let str = this.getMarked(this.joinMatching(d, agg));
            if (agg.always || str.includes('<span class="bg-')) {
              aggs[i].data = str;
            }
          }
        }
      }
    }
    return aggs;
  }

  joinMatching (data: any, agg: any): string {
    let q = (this.params.q || '').trim().toLowerCase(),
        a = (this.params[agg.param || agg.id] || '').trim().toLowerCase(),
        newData = [],
        max = 5;

    if (agg.prop) {
      data = data.filter((d: any) => d[agg.prop]).map((d: any) => String(d[agg.prop]));
    }

    data.forEach((d: string) => {
      d = d.toLowerCase();
      if ((q && d.includes(q)) || (a && d.includes(a))) {
        newData.unshift(d);
      } else {
        newData.push(d);
      }
    });

    return newData.slice(0, max).map((s: string) => {
      return agg.sentence ? utils.sentenceCase(s) : s;
    }).join(', ');
  }

  getMarked (text: string): string {
    let q = [];
    let valid = [
      'q', 'subjectUri', 'subjectLabel', 'isPartOf', 'temporal', 'range', 'archaeologicalResourceType',
      'derivedSubject', 'nativeSubject', 'keyword', 'publisher', 'contributor', 'geogrid'
    ];

    for (let key in this.params) {
      if (this.params[key] && valid.includes(key)) {
        q.push(this.params[key]);
      }
    }
    return utils.getMarked(text, q.join('|'));
  }

  @Watch('$route')
  async onRouteUpdate () {
    await this.$store.dispatch('setSearch', { fromRoute: true });
    this.setMeta();
  }
}
</script>

<style>
.link-trigger:hover h3{
  text-decoration: underline;
}
</style>
