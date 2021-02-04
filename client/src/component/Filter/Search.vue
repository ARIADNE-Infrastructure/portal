<template>
  <form
    class="relative flex flex-col sm:flex-row w-full"
    :class="{ 'lg:flex-col hg:flex-row': breakHg }"
    @submit.prevent="submit"
    v-on:keydown.esc="leave"
    v-on-clickaway="leave"
  >
    <!-- fields -->
    <b-select
      v-if="showFields"
      class="w-full sm:w-10x order-last sm:order-first"
      :class="{ 'lg:w-full hg:w-10x lg:order-last hg:order-first': breakHg }"
      :flatRight="true"
      :color="color"
      :options="fields"
      v-model="fieldValue"
      @change="doAutocomplete"
    />

    <div class="flex w-full">
      <!-- text -->
      <input
        class="flex-1 outline-none border-gray px-sm text-md border-base border-r-0 rounded-base rounded-r-0 rounded-l-0 placeholder-darkGray"
        :class="`${ focus } ${ big ? 'py-sm' : 'py-xxs' }${ autoFocus ? ' auto-focus' : ''}`"
        type="text"
        :placeholder="placeholder"
        v-model="newSearch"
        @input="doAutocomplete"
      >

      <!-- send -->
      <button
        class="rounded-base text-sm rounded-l-0 py-xs transition-all duration-300 focus:outline-none px-base"
        :class="`${ bg } ${ hover }`"
      >
        <i
          class="fas fa-search text-white"
        />
      </button>
    </div>

    <div
      v-if="autocomplete"
      class="absolute left-0 w-full bg-white top-full border-gray border-base overflow-y-scroll max-h-500 z-1002"
    >
      <div v-if="Array.isArray(autocomplete)">
        <span
          v-for="(hit, key) in autocomplete"
          :key="key"
          v-on:click.prevent="autocompleteClick($event, hit)"
          class="cursor-pointer block text-black text-md px-md py-sm border-b-base border-gray last:border-b-0 hover:bg-lightGray transition-bg duration-300"
        >
          <b v-html="utils.getMarked(utils.sentenceCase(hit.label), newSearch)"></b>
          <span v-if="hit.variants.length" v-html="' / ' + utils.getMarked(hit.variants.map(variant => utils.sentenceCase(variant.label)).join(' / '), newSearch)"></span>
          <i class="fas fa-info-circle text-blue px-sm transition-color duration-300 hover:text-green"></i>
        </span>
      </div>
      <p v-else class="px-md py-base text-md"
        v-html="autocomplete">
      </p>
    </div>
  </form>
</template>

<script lang="ts">
import { Vue, Component, Prop, Watch } from 'vue-property-decorator';
import { general, resource, search } from "@/store/modules";
import { directive as onClickaway } from 'vue-clickaway';
import utils from '@/utils/utils';
import BSelect from '@/component/Base/Select.vue';
import BLink from '@/component/Base/Link.vue';

@Component({
  components: {
    BSelect,
    BLink,
  },
  directives: {
    onClickaway
  }
})
export default class FilterSearch extends Vue {
  // props
  @Prop() color!: string;
  @Prop() bg!: string;
  @Prop() hover!: string;
  @Prop() focus!: string;
  @Prop() useCurrentSearch!: boolean;
  @Prop({ default: false }) clearSearch?: boolean;
  @Prop() showFields!: boolean;
  @Prop() big?: boolean;
  @Prop() autoFocus?: boolean;
  @Prop() hasAutocomplete?: boolean;
  @Prop() breakHg?: boolean;
  @Prop({default: false }) stayOnPage?: boolean;

  // store
  general = general;
  resource = resource;
  search = search;

  // data
  utils = utils;
  timeout: any = false;
  autocomplete: Array<any> | string = '';
  newSearch: string = '';
  fieldValue: string = '';

  get placeholder(): string {
    return this.useCurrentSearch ? 'Search for resources...' : 'Start a new search...';
  }

  get fields(): any[] {
    return resource.getFields;
  }

  doAutocomplete() {
    // skip this unless specified in prop
    if (!this.hasAutocomplete) {
      return;
    }

    const value = this.newSearch.trim().toLowerCase();
    const validFieldValue = !this.fieldValue || this.fieldValue === 'nativeSubject';

    // skip & clear results if invalid value/field value
    if (!value || !validFieldValue) {
      this.autocomplete = '';
      return;
    }

    let cached = search.getAutoComplete[value];

    if (cached !== undefined) {
      this.autocomplete = cached.length ? cached : 'No subjects found..';
      return;
    }

    this.autocomplete = '<i class="fas fa-circle-notch fa-spin mr-xs"></i> Loading subjects...';

    clearTimeout(this.timeout);
    this.timeout = setTimeout(async() => {
      await search.setAutocomplete(value);
      let stored = search.getAutoComplete[value];
      this.autocomplete = stored && stored.length ? stored : 'No subjects found..';
    }, 500);
  }

  autocompleteClick (e: any, hit: any) {
    let path = `/subject/${ hit.id }`

    if (!e.target.className || !e.target.className.includes('fas')) {
      path = utils.paramsToString('/search', {
        subjectUri: hit.id,
        subjectLabel: hit.label.toLowerCase()
      });
    }

    this.$router.push(path);
  }

  leave() {
    this.autocomplete = '';
  }

  submit() {
    this.newSearch = this.newSearch.trim();
    this.autocomplete = '';

    const searchValues: any = {
      clear: !this.useCurrentSearch || this.clearSearch,
      q: this.newSearch,
      fields: this.fieldValue
    }

    if (!this.stayOnPage) {
      searchValues.path = '/search';
    }

    search.setSearch(searchValues);

    // reset field
    if (!this.useCurrentSearch) {
      this.newSearch = '';
    }

    this.$emit('submit');
  }

  @Watch('search.getParams', { immediate: true })
  setNewSearch() {
    if (this.useCurrentSearch) {
      this.newSearch = search.getParams.q;
      this.fieldValue = search.getParams.fields || '';
    }
  }
}
</script>
