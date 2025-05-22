<template>
  <form
    class="w-full"
    @submit.prevent="submit"
    v-on:keydown.esc="leave"
    v-click-away="leave"
  >
    <div
      v-if="showFields === 'radio'"
      class="flex items-center justify-center mb-sm"
    >
      <div
        v-for="field in fields"
        :key="field.val"
      >
        <help-tooltip
          :top="field.val === 'aatSubjects' ? '-4rem' : '-2.5rem'"
          left="0"
          messageClasses="bg-yellow text-white"
        >
          <b-radio
            class="mr-xl text-white"
            name="field"
            :currentValue="fieldValue"
            :value="field.val"
            :label="field.text"
            @input="onRadioInput"
          />

          <template v-slot:content>
            <div v-html="getHelpTextOfId(field.val)" />
          </template>
        </help-tooltip>
      </div>

      <search-help />
    </div>

    <div class="flex items-center w-full">
      <div
        v-if="showFields === 'select' && !veryBig"
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
        class="relative flex flex-col w-full"
        :class="{ 'sm:flex-col hg:flex-row': breakHg, 'sm:flex-row': !veryBig }"
      >
        <b-select
          v-if="showFields === 'select'"
          class="w-full sm:w-10x"
          :class="{ 'sm:w-full hg:w-10x': breakHg }"
          :maxWidth="110"
          :color="color"
          :options="fields"
          v-model:value="fieldValue"
          @input="onSelectInput"
        />

        <div class="flex w-full">
          <!-- text -->
          <input
            class="flex-1 outline-none border-gray border-base border-r-0 placeholder-darkGray"
            :class="`${ focusStyle } ${ veryBig ? 'py-mmd' : (big ? 'py-sm' : 'py-xxs') }
              ${ autoFocus ? ' auto-focus' : ''}
              ${ veryBig ? 'text-mmd px-md' : 'text-md px-sm' }`"
            type="text"
            :placeholder="placeholder"
            v-model="newSearch"
            @input="onInputInput"
          >

          <!-- send -->
          <button
            class="py-xs transition-all duration-300 focus:outline-none"
            :class="sendButtonActiveStyle + ' ' + (veryBig ? 'px-lg text-mmd' : 'px-base text-sm')"
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
          :absolute="!veryBig"
        />
      </div>
    </div>
  </form>
</template>

<script setup lang="ts">

import { watch } from 'vue';
import { $ref, $computed, $$ } from 'vue/macros';
import { useRoute, onBeforeRouteLeave } from 'vue-router'
import { resourceModule, searchModule} from "@/store/modules";
import utils from '@/utils/utils';
import BSelect from '@/component/Base/Select.vue';
import BRadio from '@/component/Base/Radio.vue';
import HelpTooltip from '@/component/Help/Tooltip.vue';
import FilterSearchAutocomplete from './Search/Autocomplete.vue';
import SearchHelp from './Search/SearchHelp.vue';

const emit = defineEmits(['submit']);
const props = defineProps<{
  color: string,
  hoverStyle?: string,
  focusStyle?: string,
  useCurrentSearch?: boolean,
  clearSearch?: boolean,
  showFields?: string,
  big?: boolean,
  veryBig?: boolean,
  autoFocus?: boolean,
  hasAutocomplete?: boolean,
  breakHg?: boolean,
  stayOnPage?: boolean,
}>();

const route = useRoute();

let updateComponents: number = $ref(-1);
let newSearch: string = $ref('');
let fieldValue: string = $ref('');

let totalRecordsCount = $computed(() => searchModule.getTotalRecordsCount);
if (!parseInt(totalRecordsCount)) {
  searchModule.setTotalRecordsCount();
}

const canUseSendButton: boolean = $computed(() => fieldValue !== 'aatSubjects');
const placeholder: any = $computed(() => props.useCurrentSearch?'Search '+totalRecordsCount+' resources...':'Search in '+totalRecordsCount+' resources...');
const helpTexts = $computed(() => searchModule.getHelpTexts);
const fields = $computed(() => resourceModule.getFields);



const sendButtonActiveStyle: string = $computed(() => {
  if (canUseSendButton) {
    return `bg-${ props.color } border-${ props.color } ${ props.hoverStyle } border-base`;
  }
  return `bg-${ props.color }-50 border-${ props.color }-50 border-base cursor-not-allowed`;
});

const getHelpTextOfId = (id: string): string => {
  return helpTexts.find((item: any) => item.id === id)?.text || '';
}

const leave = () => {
  updateComponents = -1;
}

const submit = () => {
  updateComponents = -1;
  newSearch = newSearch.trim();

  const searchValues: any = {
    clear: !props.useCurrentSearch || props.clearSearch,
    q: newSearch,
    fields: fieldValue,
    page: 0,
  }

  if (!props.stayOnPage) {
    searchValues.path = '/search';
  }

  searchModule.setSearch(searchValues);

  // reset field
  if (!props.useCurrentSearch) {
    newSearch = '';
  }

  emit('submit');
}

const onRadioInput = (event: any) => {
  fieldValue = event.target._value;
  updateComponents++;
}

const onSelectInput = (val: string) => {
  fieldValue = val;
  updateComponents++;
}

const onInputInput = () => {
  updateComponents++
}

const unwatch = watch(route, () => {
  if (props.useCurrentSearch) {
    newSearch = searchModule.getParams.q || '';
    fieldValue = searchModule.getParams.fields || '';
  }
}, { immediate: true });

onBeforeRouteLeave(unwatch);
</script>
