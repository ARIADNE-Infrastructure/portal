<template>
  <div>
    <p v-if="!isLoading && !title && !searchResult.total">
      No results found
    </p>

    <div
      v-show="title || hasChartData"
      class="w-max-full relative"
    >
      <div :class="{ 'rounded-base mb-lg border-base border-gray': title }">
        <div v-if="title" class="rounded-t-base bg-lightGray items-center p-sm flex justify-between">
          <span class="text-md">{{ title }}</span>

          <div class="flex items-center">
            <!-- a test to disable search functionality from the sidebar version, similar to minimap -->

            <!-- <help-tooltip -->
            <!--   v-if="isZoomed" -->
            <!--   class="mr-sm" -->
            <!--   top="-.25rem" -->
            <!--   right="1.75rem" -->
            <!--   :title="`Search range: ${ getSearchRange() }`" -->
            <!-- > -->
            <!--   <i class="fas fa-search text-blue mr-sm transition-color cursor-pointer duration-300 hover:text-green" -->
            <!--     @click.prevent="navigateToRange('/search')"> -->
            <!--   </i> -->
            <!-- </help-tooltip> -->

            <button
              class="bg-yellow px-md py-sm text-center text-sm text-white cursor-pointer hover:bg-green transition-color rounded-base duration-300"
              @click="navigateToRange('/browse/when')"
            >
              <i class="fas fa-search mr-xs" />
              Advanced Search
            </button>
          </div>
        </div>
        <div v-else>
          <div
            v-if="!isLoading"
            class="absolute top-2xl right-0 shadow-full z-10 rounded-base"
          >
            <div class="text-center">
              <div
                v-if="searchResult.error"
                class="bg-white p-md text-mmd border-b-base border-red text-red rounded-t-base text-mmd"
              >
                {{ searchResult.error}}
              </div>

              <div class="bg-white p-md text-mmd rounded-t-base">
                Range: {{ getSearchRange() }}
              </div>

              <div
                class="bg-yellow py-md px-base text-mmd text-white cursor-pointer hover:bg-green transition-color rounded-b-base duration-300"
                @click="navigateToRange('/search')"
              >
                <i class="fas fa-search mr-sm"></i>
                Display as search result
              </div>
            </div>
          </div>
        </div>

        <canvas
          v-show="hasChartData"
          :id="timelineId"
          :class="{ 'pr-sm pt-md pointer-events-none': title }"
        />

        <filter-years-and-periods
          v-if="title"
          class="p-md pt-lg"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { watch, onMounted, onUnmounted } from 'vue';
import { $ref, $computed, $$ } from 'vue/macros'
import { generalModule, searchModule } from "@/store/modules";
import utils from '@/utils/utils';
import Chart from 'chart.js';
import 'chartjs-plugin-zoom';
import HelpTooltip from '@/component/Help/Tooltip.vue';
import FilterYearsAndPeriods from '@/component/Filter/YearsAndPeriods.vue';

defineProps({
  title: String,
  hasLoader: Boolean,
});

const timelineId: string = 'timeline-' + utils.getUniqueId();
let chart: any = null;
let ctx: any = null;

let range: any = $ref(null);
let hasChartData: boolean = $ref(false);
let zoomLen: number = $ref(0);
let isZoomed: boolean = $ref(false);

const window = $computed(() => generalModule.getWindow);
const isLoading: boolean = $computed(() => generalModule.getIsLoading || searchModule.getIsAggsLoading);
const searchResult = $computed(() => searchModule.getAggsResult);
const result = $computed(() => searchModule.getAggsResult.aggs);
const params = $computed(() => searchModule.getParams);

onMounted(() => {
  if (result?.range_buckets?.range_agg?.buckets){
    setTimeLine();
  }
});

onUnmounted(() => {
  if (chart) {
    chart.destroy();
    chart = null;
    hasChartData = false;
  }
});

const setTimeLine = () => {
  const buckets = result?.range_buckets?.range_agg?.buckets;
  range = null;
  isZoomed = false;

  if (isLoading) {
    return;
  }
  if (utils.objectIsEmpty(buckets) || !Object.values(buckets).some((b: any) => b.doc_count)) {
    if (chart) {
      chart.destroy();
      chart = null;
      hasChartData = false;
    }
    return;
  }

  if (!ctx) {
    ctx = (document.getElementById(timelineId) as any).getContext('2d');
  }

  if (chart) {
    chart.data = getData(buckets);
    chart.update();
    chart.resetZoom();

  } else {
    chart = new Chart(ctx, {
      type: 'line',
      data: getData(buckets),
      options: {
        scales: {
          yAxes: [{
            scaleLabel: {
              display: true,
              labelString: 'Number of resources',
              padding: 10.5, // "md in px"
            }
          }],
        },
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
              return `Year: ${ getUnformatted(a[0].label, ' to ') }`;
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

            searchModule.setSearch({
              range: getUnformatted(label, ','),
              page: 0
            });
          }
        },
        onResize: () => {
          chart.data = getData(buckets);
        },
        plugins: {
          zoom: {
            pan: {
              enabled: !utils.isMobile(),
              mode: 'x',
              speed: 10,
              threshold: 10,
              onPanComplete: ({ chart }: { chart: any }) => {
                setRange(chart);
              }
            },
            zoom: {
              enabled: !utils.isMobile(),
              drag: false,
              mode: 'x',
              speed: 1,
              threshold: 4,
              sensitivity: 0.1,
              onZoomComplete: ({ chart }: { chart: any }) => {
                setRange(chart);
              }
            }
          }
        }
      }
    });
    hasChartData = true;
  }
}

const getFormatted = (val: string): string => {
  if (val.endsWith('000') && Math.abs(parseInt(val)) > 1000) {
    return val.slice(0, -3) + 'k';
  }
  return val;
}

const getUnformatted = (val: string, delim: string): string => {
  return val.replace(/k/g, '000').replace(' - ', delim);
}

const getData = (buckets: any): any => {
  let newBuckets: any = {}

  for (let key in buckets) {
    let arr = key.split(':');
    buckets[key].val = getFormatted(arr[0]) + ' - ' + getFormatted(arr[1]);
    newBuckets[parseInt(arr[0])] = buckets[key];
  }

  let keys = Object.keys(newBuckets);
  zoomLen = keys.length;
  keys.sort((a, b) => parseInt(a) - parseInt(b));

  let values = [],
      labels = [];

  for (let i = 0; i < keys.length; i++) {
    values.push(newBuckets[keys[i]].doc_count);
    labels.push(newBuckets[keys[i]].val);
  }

  let width = (document.getElementById(timelineId) as any).parentElement.parentElement.parentElement.getBoundingClientRect().width;
  let gradient = ctx.createLinearGradient(0, 0, 0, Math.round(width / 2));
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

const getSearchRange = (): string => {
  if (range) {
    return range;
  }
  if (chart?.data?.labels?.length) {
    let from = getUnformatted(chart.data.labels[0], ',').split(',')[0],
        to = getUnformatted(chart.data.labels[chart.data.labels.length - 1], ',').split(',')[1];
    return from + ',' + to;
  }
  return '';
}

const setRange = (_chart: any) => {
  const scaleRange = _chart?.scales?.['x-axis-0']?.ticks;

  if (scaleRange && scaleRange.length) {
    isZoomed = scaleRange.length !== zoomLen;

    if (scaleRange.length === 1) {
      range = getUnformatted(scaleRange[0], ',');

    } else {
      let from = getUnformatted(scaleRange[0], ',').split(',')[0],
          to = getUnformatted(scaleRange[scaleRange.length - 1], ',').split(',')[1];

      range = from + ',' + to;
    }
  } else {
    range = null;
    isZoomed = false;
  }
}

const navigateToRange = (path: string) => {
  params.path = path;

  let searchRange = getSearchRange();

  if (searchRange && isZoomed) {
    params.range = searchRange;
  }

  searchModule.setSearch(params);
}

watch($$(window), () => {
  if (chart) {
    chart.resize();
  }
});
watch($$(isLoading), setTimeLine);
</script>
