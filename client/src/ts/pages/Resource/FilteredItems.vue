<template>
  <div v-if="items && Array.isArray(items) && items.length && filtered.length">
    <h3 v-if="header" class="text-lg font-bold mt-2x mb-md">{{ header }}</h3>
    <b v-else-if="title" class="mr-xs" :class="filtered.length > 1 ? 'block mb-xs' : ''">{{ title }}:</b>
    <div v-for="(item, key) in filtered" :key="key" :class="{ 'inline': filtered.length === 1 && title, 'mb-sm': header || icon }">
      <span v-if="slotType">
        <router-link :to="getUrl(item)" :class="utils.linkClasses()">
          <i v-if="icon" :class="icon + ' mr-xs'"></i>
          <span v-if="slotType === 'person'">
            {{ item.name + (item.type ? ' (' + item.type + ')' : '') }}
          </span>
          <span v-else-if="slotType === 'prop'">
            {{ utils.sentenceCase(item[prop || filter]) }}
          </span>
          <span v-else-if="slotType === 'link'">
            <slot :item="item"></slot>
          </span>
          <span v-else-if="slotType === 'resource'">
            <span v-if="prop">{{ item[prop] }}</span>
            <span v-else>{{ item.title || 'No title' }}</span>
          </span>
          <span v-else>
            {{ utils.sentenceCase(item) }}
          </span>
        </router-link>
      </span>
      <span v-else>
        <slot :item="item" :last="key === filtered.length - 1"></slot>
      </span>
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Prop } from 'vue-property-decorator';
import utils from '../../utils/utils';

@Component
export default class FilteredItems extends Vue {
  @Prop() items: Array<any>;
  @Prop() title?: string;
  @Prop() header?: string;
  @Prop() filter?: string;
  @Prop() prop?: string;
  @Prop() slotType?: string;
  @Prop() query?: string;
  @Prop() icon?: string;
  @Prop() fields?: string;

  utils = utils;

  get filtered () {
    if (!this.filter) {
      return this.items;
    }

    let filter = this.filter.split(',');

    return this.items.filter((item: any) => {
      return filter.some((prop: string) => item[prop] && !utils.isInvalid(item[prop]));
    });
  }

  getUrl (item: any) {
    if (this.slotType === 'resource') {
      return '/resource/' + encodeURIComponent(item[this.prop || 'id']);
    }

    let params = {
      [this.query || 'q']: this.prop ? item[this.prop] : (this.filter ? item[this.filter] : item)
    }
    if (this.query === 'subjectUri') {
      params.subjectLabel = item.label.toLowerCase();
    }
    if (this.fields) {
      params.fields = this.fields;
    }
    return utils.paramsToString('/search', params);
  }
}
</script>
