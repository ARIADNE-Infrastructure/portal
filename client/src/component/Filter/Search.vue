<template>
  <form
    class="w-full"
    @submit.prevent="submit"
    v-on:keydown.esc="leave"
    v-on-clickaway="leave"
  >
    <div
      v-if="showFields === 'radio'"
      class="flex items-center mb-sm"
    >
      <div
        v-for="field in fields"
        :key="field.val"
      >
        <help-tooltip
          top="-2.25rem"
          left="0"
          messageClasses="bg-blue text-white"
        >
          <b-radio
            class="mr-xl"
            name="field"
            :currentValue="fieldValue"
            :value="field.val"
            :label="field.text"
            @input="fieldValue = $event.target._value; updateComponents++"
          />

          <template v-slot:content>
            <div v-html="getHelpTextOfId(field.val)" />
          </template>
        </help-tooltip>
      </div>
    </div>

    <div class="flex items-center w-full">
      <div
        v-if="showFields === 'select'"
        class="p-sm pl-none pr-md"
      >
        <help-tooltip
          top="-.25rem"
          left="1rem"
          messageClasses="bg-blue text-white"
        >
          <i class="fas fa-question-circle" />

          <template v-slot:content>
            <ul class="p-sm -mb-md">
              <li
                v-for="helpText of helpTexts"
                :key="helpText.title"
                class="mb-md"
              >
                <strong>{{ helpText.title }}</strong>
                <div v-html="helpText.text" />
              </li>
            </ul>
          </template>
        </help-tooltip>
      </div>

      <div
        class="relative flex flex-col sm:flex-row w-full"
        :class="{ 'sm:flex-col hg:flex-row': breakHg }"
      >
        <b-select
          v-if="showFields === 'select'"
          class="w-full sm:w-10x"
          :class="{ 'sm:w-full hg:w-10x': breakHg }"
          selectClass="sm:rounded-r-0"
          :color="color"
          :options="fields"
          v-model="fieldValue"
          @input="updateComponents++"
        />

        <div class="flex w-full">
          <!-- text -->
          <input
            class="flex-1 outline-none border-gray px-sm text-md border-base border-r-0 rounded-base rounded-r-0 sm:rounded-l-0 placeholder-darkGray"
            :class="`${ focusStyle } ${ big ? 'py-sm' : 'py-xxs' }${ autoFocus ? ' auto-focus' : ''}`"
            type="text"
            :placeholder="placeholder"
            v-model="newSearch"
            @input="updateComponents++"
          >

          <!-- send -->
          <button
            class="rounded-base text-sm rounded-l-0 py-xs transition-all duration-300 focus:outline-none px-base"
            :class="sendButtonActiveStyle"
            :disabled="!canUseSendButton"
          >
            <i
              class="fas fa-search text-white"
            />
          </button>
        </div>

        <!-- autocomplete -->
        <filter-search-autocomplete
          v-if="hasAutocomplete"
          :newSearch="newSearch"
          :fieldValue="fieldValue"
          :stayOnPage="stayOnPage"
          :updateComponents="updateComponents"
        />
      </div>
    </div>
  </form>
</template>

<script lang="ts">
import { Vue, Component, Prop, Watch } from 'vue-property-decorator';
import { resourceModule, searchModule} from "@/store/modules";
import { helpText } from "@/store/modules/Search";
import { directive as onClickaway } from 'vue-clickaway';
import utils from '@/utils/utils';
import BSelect from '@/component/Base/Select.vue';
import BRadio from '@/component/Base/Radio.vue';
import HelpTooltip from '@/component/Help/Tooltip.vue';
import FilterSearchAutocomplete from './Search/Autocomplete.vue';

@Component({
  components: {
    BSelect,
    BRadio,
    HelpTooltip,
    FilterSearchAutocomplete,
  },
  directives: {
    onClickaway
  }
})
export default class FilterSearch extends Vue {
  // props
  @Prop() color!: string;
  @Prop() hoverStyle!: string;
  @Prop() focusStyle!: string;
  @Prop() useCurrentSearch!: boolean;
  @Prop({ default: false }) clearSearch?: boolean;
  @Prop() showFields!: string;
  @Prop() big?: boolean;
  @Prop() autoFocus?: boolean;
  @Prop() hasAutocomplete?: boolean;
  @Prop() breakHg?: boolean;
  @Prop({default: false }) stayOnPage?: boolean;

  // store
  searchModule = searchModule;

  // data
  utils = utils;
  updateComponents: number = -1;
  newSearch: string = '';
  fieldValue: string = 'all';

  get canUseSendButton(): boolean {
    return this.fieldValue === 'aatSubjects' ? false : true;
  }

  get sendButtonActiveStyle(): string {
    if (this.canUseSendButton) {
      return `bg-${ this.color } border-${ this.color } ${ this.hoverStyle } border-base`;
    }

    return `bg-${ this.color }-50 border-${ this.color }-50 border-base cursor-not-allowed`;
  }

  get helpTexts(): helpText[] {
    return searchModule.getHelpTexts;
  }

  getHelpTextOfId(id: string): string {
    const match = this.helpTexts.find((item: helpText) => item.id === id);

    return match ? match.text : '';
  }

  get placeholder(): string {
    return this.useCurrentSearch ? 'Search for resources...' : 'Start a new search...';
  }

  get fields(): any[] {
    return resourceModule.getFields;
  }

  leave(): void {
    this.updateComponents = -1;
  }

  submit(): void {
    this.updateComponents = -1;
    this.newSearch = this.newSearch.trim();

    const searchValues: any = {
      clear: !this.useCurrentSearch || this.clearSearch,
      q: this.newSearch,
      fields: this.fieldValue
    }

    if (!this.stayOnPage) {
      searchValues.path = '/search';
    }

    searchModule.setSearch(searchValues);

    // reset field
    if (!this.useCurrentSearch) {
      this.newSearch = '';
    }

    this.$emit('submit');
  }

  @Watch('searchModule.getParams', { immediate: true })
  setNewSearch() {
    if (this.useCurrentSearch) {
      this.newSearch = searchModule.getParams.q;
      this.fieldValue = searchModule.getParams.fields || 'all';
    }
  }
}
</script>