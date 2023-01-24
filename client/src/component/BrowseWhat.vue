<template>
  <div class="pt-3x px-base max-w-screen-xl mx-auto relative flex flex-col lg:flex-row">
    <!-- filters (mobile & desktop) -->
    <template v-if="window.innerWidth">
      <filter-toggleable v-if="window.innerWidth < 1000" class="lg:w-1/3 lg:pr-lg">
        <filter-list :show="['search', 'yearsAndPeriods', 'aggregations']" />
      </filter-toggleable>
      <aside v-else class="lg:w-1/3 lg:pr-lg">
        <filter-list :show="['search', 'yearsAndPeriods', 'aggregations']" />
      </aside>
    </template>

    <!-- main content -->
    <section class="lg:w-2/3 lg:pl-lg block cloud-wrapper">
      <h2 class="text-2xl">Browse what</h2>
      <p class="mb-lg">Click on a word in the word cloud to make a search in the catalogue.</p>

      <p v-if="(!wordCloud && !isLoading) || errorText" class="text-red">
        {{ errorText || 'Internal error. Could not load cloud.' }}
      </p>
      <canvas class="max-w-full mx-auto" id="cloud" :class="{ 'cursor-pointer': hovering }" @mouseleave="leaveCanvas"></canvas>
      <span class="fixed bg-black-80 p-sm text-white transition-opacity duration-300" :class="hovering ? 'opacity-100' : 'opacity-0'" ref="cloudTooltip"></span>
    </section>
  </div>
</template>

<script setup lang="ts">
import { onMounted, watch, $$ } from 'vue';
import { $ref, $computed } from 'vue/macros';
import { useRoute, onBeforeRouteLeave } from 'vue-router'
import { generalModule, searchModule } from "@/store/modules";
import WordCloud from 'wordcloud';
import FilterList from '@/component/Filter/List.vue';
import FilterToggleable from '@/component/Filter/Toggleable.vue';
import utils from '@/utils/utils';

const route = useRoute();

let error: string = $ref('');
let hovering: boolean = $ref(false);
let cloudTooltip: any = $ref(null);

const window = $computed(() => generalModule.getWindow);
const result = $computed(() => searchModule.getAggsResult?.aggs);
const errorText: string = $computed(() => error || searchModule.getAggsResult.error || '');
const isLoading: boolean = $computed(() => generalModule.getIsLoading || searchModule.getIsAggsLoading);

const wordCloud = $computed(() => {
  const buckets = result?.derivedSubject?.buckets;
  if (!buckets?.length) {
    return [];
  }

  const max = Math.max.apply(null, buckets.map((word: any) => word.doc_count));

  return buckets.map((element: any) => {
    let count = Math.round(((element.doc_count) / max) * 100);
    count = Math.min(Math.max(count, 20), 100);
    return [element.key, count];
  });
});

// Do search and set result.
onMounted(async () => {
  await searchModule.setSearch({
    fromRoute: true
  });

  startWordCloud();
});

const startWordCloud = () => {
  const canvas: any = document.getElementById('cloud');
  const rect = document.querySelector('.cloud-wrapper')!.getBoundingClientRect();
  canvas.width = Math.round(rect.width);
  canvas.height = Math.min(Math.max(300, window.innerHeight - 100), 500);

  try {
    WordCloud(
      canvas, {
      list: wordCloud,
      fontFamily: 'Roboto',
      gridSize: Math.round(16 * canvas.width / 1024),
      rotateRatio: 0,
      rotationSteps: 2,
      shrinkToFit: true,
      drawOutOfBound: false,
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
          cloudTooltip.textContent = word[0];
          cloudTooltip.style.cssText = `left:${ e.pageX + 15 }px;top:${ e.pageY - 40 }px;`;
          hovering = true;
        } else {
          hovering = false;
        }
      },
      click: (word: any) => {
        if (word) {
          searchModule.setSearch({
            path: '/search',
            clear: false,
            derivedSubject: word[0]
          });
        }
      }
    })
  } catch (ex) {
    error = 'Your browser does not support the word cloud function';
  }
}

const leaveCanvas = () => {
  hovering = false;
}

const watches = [
  watch($$(window), () => {
    if (wordCloud) {
      utils.debounce('wordCloud', () => {
        (cloudTooltip as any).style.opacity = 0;
        hovering = false;
        startWordCloud();
      }, 1000);
    }
  }),

  watch($$(result), startWordCloud),

  watch(route, () => {
    searchModule.setSearch({ fromRoute: true });
  }),
];

onBeforeRouteLeave(() => watches.forEach(unwatch => unwatch()));
</script>
