<template>
  <div>
    <h1 class="text-3x mb-md">Ariadne Theme</h1>
    <div class="bg-blue-20 px-md py-lg mb-xl text-bold">
      <p class="mb-sm">Only classes used in the app are rendered in style, except widths/margins etc.</p>
      <p>The rest are purged by PurgeCSS</p>
    </div>

    <div v-for="(conf, confKey) in config" :key="confKey">
      <accordion :title="utils.sentenceCase(utils.splitCase(confKey))"
        :initShow="true" :hover="true">
        <div v-for="(val, key) in conf" :key="key" class="p-base border-t-base border-gray"
          :class="getClassType(confKey, key, val)">
          {{ key }}: {{ val }}
        </div>
      </accordion>
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import Config from '../../../tailwind.config.js';
import utils from '../utils/utils';
import Accordion from '../components/Form/Accordion.vue';

@Component({
  components: {
    Accordion
  }
})
export default class Theme extends Vue {
  config = Config.theme;
  utils = utils;

  getClassType (confKey: any, key: any, val: any): string {
    if (/color/i.test(confKey)) {
      return `bg-${ key } ${ this.getColorContrast(val) }`;
    }
    switch (confKey) {
      case 'fontSize': return `text-${ key }`;
      case 'boxShadow': return `shadow-${ key }`;
      case 'opacity': return `opacity-${ key }`;
    }
    return '';
  }

  getColorContrast (color: any): string {
    let r, g, b, hsp;

    // convert hex to rgb
    color = +('0x' + color.slice(1).replace(
      color.length < 5 && /./g, '$&$&')
    );

    r = color >> 16;
    g = color >> 8 & 255;
    b = color & 255;

    // get hsp
    hsp = Math.sqrt(
      0.299 * (r * r) +
      0.587 * (g * g) +
      0.114 * (b * b)
    );

    if (hsp > 127.5) {
      return 'text-black';
    }
    return 'text-gray';
  }
}
</script>
