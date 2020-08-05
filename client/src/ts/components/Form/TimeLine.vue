<template>
  <div class="w-max-full">
    <div v-show="chart" class="rounded-base mb-lg border-base border-lightGray">
      <div v-if="title" class="rounded-base bg-lightGray items-center p-md flex justify-between">
        <span>{{ title }}</span>
        <div class="flex items-center">
          <tooltip v-if="isZoomed"
            class="mr-xs" top="2.5rem"
            :title="`Search range: ${ getSearchRange() }`">
            <i class="fas fa-search text-blue mr-xs transition-color cursor-pointer duration-300 hover:text-green"
              v-on:click.prevent="navigateToRange(false)">
            </i>
          </tooltip>
          <tooltip title="Show in fullscreen" top="2.5rem">
            <i class="fas fa-expand transition-color duration-300 hover:text-green px-sm cursor-pointer"
              v-on:click="navigateToRange(true)"></i>
          </tooltip>
        </div>
      </div>
      <div v-else>
        <div class="fixed top-5xl right-0 z-1001" v-if="!loading">
          <div class="m-base text-lg text-center">
            <div v-if="result.error"
              class="bg-white p-base border-base border-red text-red">
              {{ result.error}}
            </div>
            <div class="bg-white p-base border-base border-yellow">
              Range: {{ getSearchRange() }}
            </div>
            <div class="bg-yellow p-base text-white cursor-pointer hover:bg-green transition-color duration-300"
              v-on:click="navigateToRange(false)">
              <i class="fas fa-search mr-xs"></i>
              Display as search result
            </div>
          </div>
        </div>
      </div>
      <div class="py-sm">
        <canvas ref="timelineRef"></canvas>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Prop, Watch } from 'vue-property-decorator';
import utils from '../../utils/utils';
import Chart from 'chart.js';
import 'chartjs-plugin-zoom';
import Tooltip from './Tooltip.vue';

@Component({
  components: {
    Tooltip
  }
})
export default class TimeLine extends Vue {
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

  get loading () {
    return this.$store.getters.loading();
  }
  get result () {
    return this.$store.getters.result();
  }
  get params () {
    return this.$store.getters.params();
  }

  @Watch('loading')
  setTimeLine () {
    const buckets = this.result?.aggs?.range_buckets?.range_agg?.buckets;
    this.range = null;
    this.isZoomed = false;

    if (this.loading) {
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

              this.$store.dispatch('setSearch', {
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
                enabled: true,
                mode: 'x',
                speed: 10,
                threshold: 10,
                onPanComplete: ({ chart }) => {
                  this.setRange(chart);
                }
              },
              zoom: {
                enabled: true,
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
      if (range && this.isZoomed) {
        params.range = range;
      }
      this.$router.push(utils.paramsToString('/browse/when', params));

    } else {
      this.$store.dispatch('setSearch', this.isZoomed ? { range } : {});
    }
  }
}
</script>
