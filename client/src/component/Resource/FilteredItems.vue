<template>
  <div v-if="filtered.length">
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
      :class="{ 'mb-sm': !isSingleItem && key < filtered.length - 1, 'inline': isSingleItem }"
    >
      <span v-if="slotType" :class="{ 'mb-md': !isSingleItem }">
        <i v-if="icon" :class="icon"></i>

        <b-link v-if="query === 'publisher' && findPublisher(item.name)"
          class="text-blue hover:underline hover:text-darkGray transition-color duration-300"
          :to="utils.paramsToString('/publisher/', { publisher: item.name })">
          <i class="fas fa-info-circle"></i>&nbsp;
        </b-link>

        <b-link :to="getUrl(item)">
          <span v-if="slotType === 'person' || slotType === 'organisation'">
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
            <span v-else>{{ item.title.text || 'No title' }}</span>
          </span>

          <span v-else>
            {{ utils.sentenceCase(item) }}
          </span>
        </b-link>

        <b-link
          v-if="getHomepage(item)"
          :href="getHomepage(item)"
          target="_blank"
          class="block mt-sm md:mt-none md:ml-md md:inline text-blue hover:underline hover:text-darkGray transition-color duration-300"
        >
          <i class="fa fa-home mr-xs"></i>
          {{ item.institution ? item.institution : getHomepage(item) }}
        </b-link>

        <span
          v-if="getEmail(item)"
          class="hover:text-black group block mt-sm md:mt-none md:ml-md md:inline"
        >
          <i class="fa fa-envelope mr-sm text-blue group-hover:text-black"></i>
          <span v-html="getEmail(item)"></span>
        </span>

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

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { generalModule } from "@/store/modules";
import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';
import HelpTooltip from '@/component/Help/Tooltip.vue';

const props = defineProps<{
  items?: Array<any>,
  title?: string,
  header?: string,
  filter?: string,
  filterUnique?: string,
  prop?: string,
  slotType?: string,
  query?: string,
  icon?: string,
  fields?: string,
}>();

const assets: string = $computed(() => generalModule.getAssetsDir);
const isSingleItem: boolean = $computed(() => filtered.length === 1 && props.title ? true : false);

const itemsList: Array<any> = $computed(() => {
  // use prop if valid
  if (Array.isArray(props.items)) {
    return props.items;
  }
  return [];
});

const filtered: Array<any> = $computed(() => {
  if (!props.filter) {
    return itemsList;
  }

  let filter = props.filter.split(',');
  let doublets: any[] = [];

  return itemsList.filter((item: any) => {
    if (filter.some((prop: string) => item[prop] && !utils.isInvalid(item[prop]))) {
      if (!doublets.some((doublet: any) => props.filterUnique ? item[props.filterUnique] === doublet[props.filterUnique] : utils.objectEquals(doublet, item))) {
        doublets.push(item);
        return true;
      }
    }
    return false;
  });
});

const getHomepage = (item: any): string | null => {
  let homepage = item?.homepage;

  if ((props.slotType === 'person' || props.slotType === 'organisation') && homepage) {
    // make sure a protocol is set
    if (!/http:|https:/.test(homepage)) {
      homepage = `http://${ homepage }`;
    }
    return homepage;
  }
  return null;
};

const getUrl = (item: any): string => {
  if (props.slotType === 'resource') {
    return '/resource/' + encodeURIComponent(item[props.prop || 'id']);
  }
  if (props.slotType === 'subject') {
    return utils.paramsToString('/subject/' + encodeURIComponent(item[props.prop || 'id']), { derivedSubject: item.prefLabel });
  }

  let params = {
    [props.query || 'q']: props.prop ? item[props.prop] : (props.filter ? item[props.filter] : item)
  }
  if (props.fields) {
    params.fields = props.fields;
  }
  return utils.paramsToString('/search', params);
}

const getEmail = (item: any): string => {
  // Only organisationss (publisher) is allowed to display email!!
  if (props.slotType === 'organisation') {
    const regex = new RegExp("^(.+)@(.+)$");
    let email = item?.email;
    if (regex.test(email)) {
      return '<a href="mailto:' + utils.escHtml(email) + '">Email</a>';
    }
  }
  return '';
}

const findPublisher = (key: string): any => generalModule.findPublisher(key);
</script>
