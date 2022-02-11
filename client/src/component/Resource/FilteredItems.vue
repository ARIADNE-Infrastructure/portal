<template>
  <div v-if="this.filtered.length">
    <h3
      v-if="header"
      class="text-lg font-bold mt-2x mb-md"
    >
      {{ header }}
    </h3>

    <b
      v-if="title"
      class="mr-xs"
      :class="filtered.length > 1 ? 'block mb-sm' : ''"
    >
      {{ title }}:
      <slot name="helpIcon"></slot>
    </b>

    <div
      v-for="(item, key) in filtered"
      :key="key"
      class="mb-sm"
      :class="{ 'inline': isSingleItem }"
    >
      <span v-if="slotType" :class="{ 'mb-md': !isSingleItem }">
        <i v-if="icon" :class="icon"></i>

        <b-link :to="getUrl(item)">
          <span v-if="slotType === 'person'">
            {{ item.name + (item.type ? ' (' + item.type + ')' : '') }}
          </span>

          <span v-else-if="slotType === 'prop' || slotType === 'subject'">
            {{ utils.sentenceCase(item[prop || filter]) }}
          </span>

          <span v-else-if="slotType === 'link'">
            <slot :item="item"></slot>
          </span>

          <span v-else-if="slotType === 'resource'">
            <span v-if="prop">{{ item[prop] }}</span>
            <span v-else>{{ item.title.text || 'No title' }}</span>
          </span>

          <span v-else>
            {{ utils.sentenceCase(item) }}
          </span>
        </b-link>

        <b-link
          v-if="getHomepage(item)"
          :href="getHomepage(item)"
          target="_blank"
          class="hover:text-black group block mt-sm md:mt-none md:ml-md md:inline"
        >
          <i class="fa fa-home mr-xs text-blue group-hover:text-black"></i>
          {{ item.institution ? item.institution : getHomepage(item) }}
        </b-link>
      </span>

      <span v-else>
        <slot
          :item="item"
          :last="key === filtered.length - 1"
        >
        </slot>
      </span>
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Vue, Prop } from 'vue-property-decorator';
import { generalModule } from "@/store/modules";

import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';
import HelpTooltip from '@/component/Help/Tooltip.vue';

@Component({
  components: {
    BLink,
    HelpTooltip
  }
})
export default class ResourceFilteredItems extends Vue {
  @Prop() items?: any[];
  @Prop() title?: string;
  @Prop() header?: string;
  @Prop() filter?: string;
  @Prop() prop?: string;
  @Prop() slotType?: string;
  @Prop() query?: string;
  @Prop() icon?: string;
  @Prop() fields?: string;

  utils = utils;

  get itemsList(): any[] {
    // use prop if valid
    if (Array.isArray(this.items)) {
      return this.items;
    }

    return [];
  }

  get isSingleItem(): boolean {
    return this.filtered.length === 1 && this.title ? true : false;
  }

  get assets(): string {
    return generalModule.getAssetsDir;
  }

  get filtered(): any[] {
    if (!this.filter) {
      return this.itemsList;
    }

    let filter = this.filter.split(',');
    let doublets: any[] = [];

    return this.itemsList.filter((item: any) => {
      if (filter.some((prop: string) => item[prop] && !utils.isInvalid(item[prop]))) {
        if (!doublets.some((doublet: any) => utils.objectEquals(doublet, item))) {
          doublets.push(item);
          return true;
        }
      }
      return false;
    });
  }

  getHomepage(item: any): string | null {
    let homepage = item?.homepage;

    if (this.slotType === 'person' && homepage) {
      // make sure a protocol is set
      if (!/http:|https:/.test(homepage)) {
        homepage = `http://${ homepage }`;
      }

      return homepage;
    }

    return null;
  }

  getUrl(item: any): string {

    if (this.slotType === 'resource' || this.slotType === 'subject') {
      return '/' + this.slotType + '/' + encodeURIComponent(item[this.prop || 'id']);
    }

    let params = {
      [this.query || 'q']: this.prop ? item[this.prop] : (this.filter ? item[this.filter] : item)
    }

    if (this.query === 'derivedSubjectId') {
      if(item.id) {
        let parts = item.id.split('/');
        params = {
          [this.query] : parts[parts.length-1]
        }
      }
    }
    if (this.fields) {
      params.fields = this.fields;
    }
    return utils.paramsToString('/search', params);
  }
}
</script>
