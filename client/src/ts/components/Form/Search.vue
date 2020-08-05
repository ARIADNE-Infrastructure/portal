<template>
  <form
    class="relative flex flex-col sm:flex-row w-full"
    v-on:submit.prevent="submit"
    v-on:keydown.esc="leave"
    v-on-clickaway="leave"
  >
    <!-- fields -->
    <base-select
      class="w-full sm:w-9x order-last sm:order-first"
      :color="color"
      :options="fields"
      v-if="showFields"
      v-model="fieldValue"
    />

    <div class="flex w-full">
      <!-- text -->
      <input
        class="flex-1 px-sm outline-none border-gray border-base border-r-0 rounded-base rounded-r-0 placeholder-darkGray"
        :class="`${ focus } ${ big ? 'py-sm' : 'py-xs' }${ autoFocus ? ' auto-focus' : '' }`"
        type="text"
        placeholder="Start a new search..."
        v-model="newSearch"
        v-on:input="doAutocomplete"
      >

      <!-- send -->
      <button
        class="rounded-base rounded-l-0 py-xs transition-all duration-300 focus:outline-none"
        :class="`${ bg } ${ hover } ${ big ? 'px-lg' : 'px-sm' }`"
      >
        <i
          class="fas fa-search text-white"
        />
      </button>
    </div>

    <div class="absolute left-0 w-full bg-white top-full border-gray border-base" v-if="autocomplete">
      <div v-if="Array.isArray(autocomplete)">
        <router-link v-for="(hit, key) in autocomplete" :key="key"
          :to="getSubjectQuery(hit)"
          class="block text-black text-mmd px-md py-sm border-b-base border-gray last:border-b-0 hover:bg-lightGray transition-bg duration-300">
          <b v-html="utils.getMarked(utils.sentenceCase(hit.label), newSearch)"></b>
          <span v-if="hit.variants.length" v-html="' / ' + utils.getMarked(hit.variants.map(variant => utils.sentenceCase(variant.label)).join(' / '), newSearch)"></span>
        </router-link>
      </div>
      <p v-else class="px-md py-base"
        v-html="autocomplete">
      </p>
    </div>
  </form>
</template>

<script lang="ts">
import { Vue, Component, Prop, Watch } from 'vue-property-decorator';
import { directive as onClickaway } from 'vue-clickaway';
import utils from '../../utils/utils';
import BaseSelect from './BaseSelect.vue';

@Component({
  components: {
    BaseSelect,
  },
  directives: {
    onClickaway
  }
})
export default class Search extends Vue {
  @Prop() color!: string;
  @Prop() bg!: string;
  @Prop() hover!: string;
  @Prop() focus!: string;
  @Prop() useCurrentSearch!: boolean;
  @Prop() showFields!: boolean;
  @Prop() big?: boolean;
  @Prop() autoFocus?: boolean;
  @Prop() hasAutocomplete?: boolean;
  utils = utils;
  timeout: any = false;
  autocomplete: Array<any> | string = '';
  newSearch: string = '';
  fieldValue: string = '';

  get fields() {
    return this.$store.getters.fields();
  }

  get params() {
    return this.$store.getters.params();
  }

  getCachedAutocomplete(search: string): string {
    let values = this.$store.getters.autocomplete()[search];
    if (values && values.length) {
      return values;
    }
    return '';
  }

  doAutocomplete() {
    let search = this.newSearch.trim().toLowerCase();

    if (!this.hasAutocomplete) {
      return;
    }
    if (!search) {
      this.autocomplete = '';
      return;
    }

    let cached = this.$store.getters.autocomplete()[search];
    if (cached !== undefined) {
      this.autocomplete = cached.length ? cached : '';
      return;
    }

    this.autocomplete = '<i class="fas fa-circle-notch fa-spin mr-xs"></i> Loading subjects...';

    clearTimeout(this.timeout);
    this.timeout = setTimeout(async() => {
      await this.$store.dispatch('setAutocomplete', search);
      let stored = this.$store.getters.autocomplete()[search];
      this.autocomplete = stored && stored.length ? stored : 'No subjects found..';
    }, 500);
  }

  getSubjectQuery (hit: any): string {
    return utils.paramsToString('/search', {
      subjectUri: hit.id,
      subjectLabel: hit.label.toLowerCase()
    });
  }

  leave() {
    this.autocomplete = '';
  }

  submit() {
    this.newSearch = this.newSearch.trim();
    this.autocomplete = '';

    this.$store.dispatch('setSearch', {
      clear: true,
      q: this.newSearch,
      fields: this.fieldValue
    });

    // reset field
    if (!this.useCurrentSearch) {
      this.newSearch = '';
    }
  }

  @Watch('params', { immediate: true })
  setNewSearch() {
    if (this.useCurrentSearch) {
      this.newSearch = this.params.q;
      this.fieldValue = this.params.fields || '';
    }
  }
}
</script>
