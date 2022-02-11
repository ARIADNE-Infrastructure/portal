<template>
  <div
    v-if="autocomplete && updateComponents !== -1"
    class="absolute left-0 w-full bg-white top-full z-1002 rounded-b-base text-left"
  >
    <template v-if="Array.isArray(autocomplete.hits)">
      <div
        class="overflow-y-scroll max-h-500 border-gray border-base"
        :class="{ 'rounded-b-base': !autocomplete.hasMoreResults }"
      >
        <span
          v-for="(hit, key) in autocomplete.hits"
          :key="key"
          v-on:click.prevent="autocompleteClick($event, hit)"
          class="cursor-pointer block text-black text-md px-md py-sm border-b-base border-gray last:border-b-0 hover:bg-lightGray transition-bg duration-300"
        >
          <!-- aat subjects -->
          <template v-if="autocompleteType === 'subjects'">
            <strong
              class="pt-xs pb-xs pr-xs inline-block"
              v-html="getAutoCompleteLabel(hit.label, newSearch)"
            />
            <!-- variants -->
            <template v-if="hit.variants.length">
              <div
                v-for="(variant, key) in hit.variants"
                :key="key"
                class="pt-xs pb-xs pr-sm inline-block"
              >
                <span class="text-midGray pr-xs">/</span>
                <span v-html="utils.getMarked(utils.sentenceCase(variant.label), newSearch)" />
                <em class="text-midGray">({{ variant.lang}})</em>
              </div>
            </template>

            <i class="fas fa-info-circle text-blue px-sm transition-color duration-300 hover:text-green"></i>
          </template>

          <!-- resources -->
          <template v-else>
            <strong
              class="py-xs inline-block"
              v-html="utils.getMarked(utils.sentenceCase(hit.label.text), newSearch)"
            />

            <!-- fields -->
            <div v-if="hit.fieldHits.length">
              Found in: <em>{{ getFieldHitLabels(hit.fieldHits) }}</em>
            </div>
          </template>
        </span>
      </div>

      <div
        v-if="autocomplete.hasMoreResults"
        class="bg-red text-white p-sm text-md rounded-b-base"
      >
        Some options are currently not visible. Use the search field above to narrow down this list.
      </div>
    </template>

    <p
      v-else
      class="px-md py-md text-md border-gray border-base rounded-b-base"
      v-html="autocomplete"
    />
  </div>
</template>

<script lang="ts">
import { Vue, Component, Prop, Watch } from 'vue-property-decorator';
import utils from '@/utils/utils';
import { searchModule, aggregationModule } from "@/store/modules";

@Component
export default class FilterSearchAutoComplete extends Vue {
  // props
  @Prop() newSearch!: string;
  @Prop() fieldValue!: string;
  @Prop() stayOnPage!: boolean;
  @Prop() updateComponents!: number;

  // data
  utils = utils;
  timeout: any = false;
  autocomplete: any = '';

  getFieldHitLabels(hits: string[]): string {
    return hits.map((field: string) => aggregationModule.getTitle(field)).join(', ');
  }

  getAutoCompleteLabel(label: string, searchString: any) {
    if(label) {
      return utils.getMarked(utils.sentenceCase(label), '')
    }
    return '*'+searchString+'*';
  }

  get autocompleteType(): string {
    return this.fieldValue === 'aatSubjects' ? 'subjects' : 'resources';
  }

  doAutocomplete() {
    const value = this.newSearch.trim().toLowerCase();

    if (!value) {
      this.autocomplete = '';
      return;
    }

    let cached = searchModule.getAutoComplete[this.fieldValue + value];

    if (cached !== undefined) {
      this.autocomplete = cached.hits?.length ? cached : `No ${ this.autocompleteType } found..`;
      return;
    }

    this.autocomplete = `<i class="fas fa-circle-notch fa-spin mr-xs"></i> Loading ${ this.autocompleteType }..`;

    clearTimeout(this.timeout);

    this.timeout = setTimeout(async() => {
      await searchModule.setAutocomplete({ type: this.fieldValue, q: value });
      let stored = searchModule.getAutoComplete[this.fieldValue + value];

      this.autocomplete = stored && stored.hits?.length ? stored : `No ${ this.autocompleteType } found..`;
    }, 500);
  }

  autocompleteClick (e: any, hit: any) {
    let path = '';
    this.autocomplete = '';

    if (this.autocompleteType === 'subjects') {

      path = `/subject/${ hit.id }`

      if (!e.target.className || !e.target.className.includes('fas')) {

        const searchValues: any = {
          derivedSubjectId: hit.id,
          derivedSubject: hit.label
        };

        if (!this.stayOnPage) {
          searchValues.path = '/search';
        }

        searchModule.setSearch(searchValues);

      }
      else {
        this.$router.push(path);
      }
    }
    else {
      path = `/resource/${ hit.id }`;
      this.$router.push(path);
    }
  }

  @Watch('updateComponents')
  onParentUpdate() {
    // on close
    if (this.updateComponents === -1) {
      this.autocomplete = '';
    }
    // on change
    else {
      this.doAutocomplete();
    }
  }
}
</script>