<template>


  <div class="pt-3x px-base max-w-screen-xl mx-auto relative flex flex-col lg:flex-row">

    <aside class="pt-base lg:pt-none lg:w-1/3 lg:pr-lg lg:order-first block order-last relative">

      <h2 class="text-lg mb-md">
        Filters
      </h2>

      <filter-search
        color="blue"
        hoverStyle="hover:bg-blue-80"
        focusStyle="focus:border-blue"
        class="mb-lg pr-lg"
        :breakHg="true"
        :big="true"
        :useCurrentSearch="true"
        showFields="select"
        :hasAutocomplete="true"
        :stayOnPage="true"
        @submit="utils.blurMobile()"
      />

      <filter-years class="mb-lg pr-lg" />
      <filter-aggregations max-height="490px" />

      <div class="pr-lg">
        <filter-clear
          :ignoreParams="['page', 'sort', 'order', 'mapq', 'bbox', 'size', 'ghp', 'loadingStatus', 'forceReload']"
        />
      </div>

    </aside>

    <section class="lg:w-2/3 lg:pl-lg block">
      <p v-if="(!wordCloud && !isLoading) || error" class="text-red">
        {{ error || 'Internal error. Could not load cloud.' }}
      </p>
      <canvas class="max-w-full mx-auto" id="cloud" :class="{ 'cursor-pointer': hovering }" @mouseleave="leaveCanvas"></canvas>
      <span class="absolute bg-black-80 p-sm text-white z-1000 transition-opacity duration-300" :class="hovering ? 'opacity-100' : 'opacity-0'" ref="cloudTooltip"></span>
    </section>

  </div>

</template>

<script lang="ts">

import { Component, Vue, Watch } from 'vue-property-decorator';
import { generalModule, searchModule } from "@/store/modules";
import WordCloud from 'wordcloud';

import FilterSearch from '@/component/Filter/Search.vue';
import FilterYears from '@/component/Filter/Years.vue';
import FilterAggregations from '@/component/Filter/Aggregations.vue';
import FilterClear from '@/component/Filter/Clear.vue';

@Component({
  // Filter components
  components: {
    FilterSearch,
    FilterYears,
    FilterAggregations,
    FilterClear
  }
})


export default class BrowseWhat extends Vue {
  error: string = '';
  hovering: boolean = false;
  timeout: any = false;

  async created () {

    // Do search and set result.
    await searchModule.setSearch({
      fromRoute: true
    });

    this.startWordCloud();
    window.addEventListener('resize', this.onResize);
  }

  // Getter for result
  get result(): any {
    return searchModule.getResult;
  }

  destroyed () {
    window.removeEventListener('resize', this.onResize);
  }

  onResize () {
    if (this.wordCloud) {
      clearTimeout(this.timeout);
      this.timeout = setTimeout(() => {
        (this.$refs.cloudTooltip as any).style.opacity = 0;
        this.hovering = false;
        this.startWordCloud();
      }, 1000);
    }
  }

  get isLoading(): boolean {
    return generalModule.getIsLoading;
  }

  get wordCloud () {
    const wordCloudElemens = new Array();

    try {
      const buckets = searchModule.getResult.aggs.derivedSubject['buckets'];
      const max = Math.max.apply(null, buckets.map((word: any) => word.doc_count));

      // Notice, bucket size is set by API backend and aggregations size.
      buckets.forEach((element: any) => {
        let count = Math.round((element.doc_count / max) * 100);
        if (count > 100) {
          count = 100;
        } else if (count < 10) {
          count = 10;
        }
        wordCloudElemens.push( [element.key,count] );
      });
    } catch(e) {
      // too soon, wait
    }

    return wordCloudElemens;
  }

  startWordCloud () {
    let canvas: any = document.getElementById('cloud');
    canvas.width = window.innerWidth;
    canvas.height = Math.max(300, window.innerHeight - 100);

    let tooltip = (this.$refs.cloudTooltip as any);

    try {

      WordCloud(
        canvas, {
        list: this.wordCloud,
        fontFamily: 'Roboto',
        gridSize: Math.round(16 * canvas.width / 1024),
        rotateRatio: 0,
        rotationSteps: 2,
        shuffle: true,
        weightFactor (size: number) {
          return size * canvas.width / 1024;
        },
        color (word: any, weight: number) {
          if (weight >= 66) {
            return '#BB3921';
          }
          if (weight >= 33) {
            return '#D5A03A';
          }
          return '#75A99D';
        },
        hover: (word: any, dimension: any, e: any) => {
          if (word) {
            tooltip.textContent = word[0];
            tooltip.style.cssText = `left:${ e.pageX + 15 }px;top:${ e.pageY - 40 }px;`;
            this.hovering = true;
          } else {
            this.hovering = false;
          }
        },
        click: (word: any) => {
          if (word) {
            searchModule.setSearch({
              path: '/search',
              clear: true,
              derivedSubject: word[0]
            });
          }
        }
      })
    } catch (ex) {
      this.error = 'Your browser does not support the word cloud function';
    }
  }

  leaveCanvas () {
    this.hovering = false;
  }

  @Watch("result")
    matchCloudToFilter () {
      this.startWordCloud();
  }

}

</script>