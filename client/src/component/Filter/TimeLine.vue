<template>
  <div>
    <p v-if="!title && (!result.hits || !result.hits.length)">
      No results found
    </p>

    <div
      v-show="title || chart"
      class="w-max-full"
    >
      <div :class="{ 'rounded-base mb-lg border-base border-gray': title }">
        <div v-if="title" class="rounded-base bg-lightGray items-center p-md flex justify-between">
          <span class="text-md">{{ title }}</span>
          <div class="flex items-center">
            <help-tooltip v-if="isZoomed"
              class="mr-xs" top="2.5rem"
              :title="`Search range: ${ getSearchRange() }`">
              <i class="fas fa-search text-blue mr-xs transition-color cursor-pointer duration-300 hover:text-green"
                @click.prevent="navigateToRange(false)">
              </i>
            </help-tooltip>
            <help-tooltip title="Show in fullscreen" top="2.5rem">
              <i class="fas fa-expand transition-color duration-300 hover:text-green px-sm cursor-pointer"
                @click="navigateToRange(true)"></i>
            </help-tooltip>
          </div>
        </div>
        <div v-else>
          <div
            v-if="!isLoading"
            class="absolute top-2xl right-0 shadow-full z-1001 rounded-base"
          >
            <div class="text-center">
              <div
                v-if="result.error"
                class="bg-white p-md text-mmd border-b-base border-red text-red rounded-t-base text-mmd"
              >
                {{ result.error}}
              </div>

              <div class="bg-white p-md text-mmd rounded-t-base">
                Range: {{ getSearchRange() }}
              </div>

              <div
                class="bg-yellow py-md px-base text-mmd text-white cursor-pointer hover:bg-green transition-color rounded-b-base duration-300"
                @click="navigateToRange(false)"
              >
                <i class="fas fa-search mr-xs"></i>
                Display as search result
              </div>
            </div>
          </div>
        </div>
        <div class="py-sm">
          <canvas v-show="chart" ref="timelineRef"></canvas>

          <filter-years
            v-if="title"
            class="px-md py-sm"
            :class="{ 'pt-base': chart }"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Prop, Watch } from 'vue-property-decorator';
import { search, general } from "@/store/modules";
import utils from '@/utils/utils';
import Chart from 'chart.js';
import 'chartjs-plugin-zoom';
import HelpTooltip from '@/component/Help/Tooltip.vue';
import FilterYears from '@/component/Filter/Years.vue';

@Component({
  components: {
    HelpTooltip,
    FilterYears,
  }
})
export default class FilterTimeLine extends Vue {
  @Prop() title?: string;
  utils = utils;
  chart: any = null;
  ctx: any = null;
  range: any = null;
  zoomLen: number = 0;
  isZoomed: boolean = false;

  mounted () {
    if (this.result) {
      this.setTimeLine();
    }
    window.addEventListener('resize', this.onResize);
  }

  get isLoading(): boolean {
    return general.getLoading;
  }

  get result(): any {
    return search.getResult;
  }

  get params(): any {
    return search.getParams;
  }

  @Watch('isLoading')
  setTimeLine () {
    const buckets = this.result?.aggs?.range_buckets?.range_agg?.buckets;
    this.range = null;
    this.isZoomed = false;

    if (this.isLoading) {
      return;
    }
    if (!utils.objectIsNotEmpty(buckets) || !Object.values(buckets).some((b: any) => b.doc_count)) {
      if (this.chart) {
        this.chart.destroy();
        this.chart = null;
      }
      return;
    }

    if (!this.ctx) {
      this.ctx = (this.$refs.timelineRef as any).getContext('2d');
    }

    if (this.chart) {
      this.chart.data = this.getData(buckets);
      this.chart.update();
      this.chart.resetZoom();

    } else {
      this.chart = new Chart(this.ctx, {
        type: 'line',
        data: this.getData(buckets),
        options: {
          aspectRatio: 1.5,
          legend: {
            display: false
          },
          elements: {
            point:{
              radius: 2
            }
          },
          tooltips: {
            callbacks: {
              title: (a: any, b: any) => {
                return `Year: ${ this.getUnformatted(a[0].label, ' to ') }`;
              },
              label: (a: any, b: any) => {
                return ` Amount: ${ a.value }`;
              }
            }
          },
          onClick: (ev: any, items: any) => {
            let labels = items?.[0]?._chart?.tooltip?._data?.labels;

            if (labels) {
              let label = labels[items[0]._index];

              search.setSearch({
                range: this.getUnformatted(label, ','),
                page: 0
              });
            }
          },
          onResize: () => {
            this.chart.data = this.getData(buckets);
          },
          plugins: {
            zoom: {
              pan: {
                enabled: !utils.isMobile(),
                mode: 'x',
                speed: 10,
                threshold: 10,
                onPanComplete: ({ chart }) => {
                  this.setRange(chart);
                }
              },
              zoom: {
                enabled: !utils.isMobile(),
                drag: false,
                mode: 'x',
                speed: 1,
                threshold: 4,
                sensitivity: 0.1,
                onZoomComplete: ({ chart }) => {
                  this.setRange(chart);
                }
              }
            }
          }
        }
      });
    }
  }

  getFormatted (val: string) {
    if (val.endsWith('000') && Math.abs(parseInt(val)) > 1000) {
      return val.slice(0, -3) + 'k';
    }
    return val;
  }

  getUnformatted (val: string, delim: string) {
    return val.replace(/k/g, '000').replace(' - ', delim);
  }

  getData (buckets: any) {
    let newBuckets = {}

    for (let key in buckets) {
      let arr = key.split(':');
      buckets[key].val = this.getFormatted(arr[0]) + ' - ' + this.getFormatted(arr[1]);
      newBuckets[parseInt(arr[0])] = buckets[key];
    }

    let keys = Object.keys(newBuckets);
    this.zoomLen = keys.length;
    keys.sort((a, b) => parseInt(a) - parseInt(b));

    let values = [],
        labels = [];

    for (let i = 0; i < keys.length; i++) {
      values.push(newBuckets[keys[i]].doc_count);
      labels.push(newBuckets[keys[i]].val);
    }

    let width = (this.$refs.timelineRef as any).parentElement.parentElement.parentElement.getBoundingClientRect().width;
    let gradient = this.ctx.createLinearGradient(0, 0, 0, Math.round(width / 2));
    gradient.addColorStop(0, '#BB3921');
    gradient.addColorStop(0.5, '#D5A03A');
    gradient.addColorStop(1, '#75A99D');

    return {
      labels: labels,
      datasets: [{
        backgroundColor: gradient,
        data: values,
      }]
    };
  }

  onResize () {
    if (this.chart) {
      this.chart.resize();
    }
  }

  destroyed () {
    if (this.chart) {
      this.chart.destroy();
      this.chart = null;
    }
    window.removeEventListener('resize', this.onResize);
  }

  getSearchRange () {
    if (this.range) {
      return this.range;
    }
    if (this.chart?.data?.labels?.length) {
      let from = this.getUnformatted(this.chart.data.labels[0], ',').split(',')[0],
          to = this.getUnformatted(this.chart.data.labels[this.chart.data.labels.length - 1], ',').split(',')[1];
      return from + ',' + to;
    }
    return '';
  }

  setRange (chart: any) {
    const range = chart?.scales?.['x-axis-0']?.ticks;

    if (range && range.length) {
      this.isZoomed = range.length !== this.zoomLen;

      if (range.length === 1) {
        this.range = this.getUnformatted(range[0], ',');

      } else {
        let from = this.getUnformatted(range[0], ',').split(',')[0],
            to = this.getUnformatted(range[range.length - 1], ',').split(',')[1];

        this.range = from + ',' + to;
      }
    } else {
      this.range = null;
      this.isZoomed = false;
    }
  }

  navigateToRange (fullscreen: boolean) {
    let range = this.getSearchRange();

    if (fullscreen) {
      let params = this.params;
      params.path = '/browse/when';

      if (range && this.isZoomed) {
        params.range = range;
      }
      search.setSearch(params);

    } else {
      search.setSearch({
        path: '/search',
      });
    }
  }
}
</script>
