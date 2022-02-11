<template>
	<div class="pt-3x pb-base px-base max-w-screen-xl mx-auto">
    <p v-if="(!wordCloud && !isLoading) || error" class="text-red">
      {{ error || 'Internal error. Could not load cloud.' }}
    </p>
    <canvas class="max-w-full mx-auto" id="cloud" :class="{ 'cursor-pointer': hovering }" @mouseleave="leaveCanvas"></canvas>
    <span class="absolute bg-black-80 p-sm text-white z-1000 transition-opacity duration-300" :class="hovering ? 'opacity-100' : 'opacity-0'" ref="cloudTooltip"></span>
  </div>
</template>

<script lang="ts">

import { Component, Vue } from 'vue-property-decorator';
import { generalModule, searchModule } from "@/store/modules";
import WordCloud from 'wordcloud';

@Component
export default class BrowseWhat extends Vue {
  error: string = '';
  hovering: boolean = false;
  timeout: any = false;

  async created () {
    // @ts-ignore
    await generalModule.setWordCloud();
    this.startWordCloud();

    window.addEventListener('resize', this.onResize);
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
    // @ts-ignore
    return generalModule.getWordCloud;
  }

  startWordCloud () {
    let canvas: any = document.getElementById('cloud');
    canvas.width = innerWidth;
    canvas.height = Math.max(300, innerHeight - 100);

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
              q: word[0]
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
}

</script>
