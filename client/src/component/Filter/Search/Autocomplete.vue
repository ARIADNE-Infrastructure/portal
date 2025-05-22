<template>
  <div
    v-if="autocomplete && updateComponents !== -1"
    class="left-0 w-full bg-white top-full z-20 text-left"
    :class="{ absolute }"
  >
    <template v-if="Array.isArray(autocomplete.hits)">
      <div
        class="overflow-y-auto max-h-500 border-gray border-base"
      >
        <span
          v-for="(hit, key) in autocomplete.hits"
          :key="key"
          v-on:click.prevent="autocompleteClick($event, hit)"
          class="cursor-pointer block text-black text-md px-md py-sm border-b-base border-gray last:border-b-0 hover:bg-lightGray transition-bg duration-300"
        >
          <!-- aat subjects -->
          <template v-if="autocompleteType === 'subjects'">
            <i class="fas fa-info-circle text-blue pr-sm transition-color duration-300 hover:text-green"></i>
            <strong
              class="pt-xs pb-xs pr-xs inline-block"
              v-html="getAutoCompleteLabel(hit.label, newSearch)"
            />
            <!-- variants -->
            <template v-if="getFilteredVariants(hit.variants)?.length">
              <div
                v-for="(variant, key) in getFilteredVariants(hit.variants)"
                :key="key"
                class="pt-xs pb-xs pr-sm inline-block"
              >
                <span class="text-midGray pr-xs">/</span>
                <span v-html="utils.getMarked(utils.sentenceCase(variant.label), newSearch)" />
                <em class="text-midGray"> ({{ variant.lang }})</em>
              </div>
            </template>
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
        class="bg-red text-white p-sm text-md"
      >
        Some options are currently not visible. Use the search field above to narrow down this list.
      </div>
    </template>

    <p
      v-else
      class="px-md py-md text-md border-gray border-base"
      v-html="autocomplete"
    />
  </div>
</template>

<script setup lang="ts">
import { watch } from 'vue';
import { $ref, $computed, $$ } from 'vue/macros';
import { useRouter } from 'vue-router'
import utils from '@/utils/utils';
import { searchModule, aggregationModule } from "@/store/modules";

const props = withDefaults(defineProps<{
  newSearch: string,
  fieldValue?: string,
  stayOnPage?: boolean,
  updateComponents?: number,
  absolute: boolean,
}>(), {
  absolute: true,
});

let autocomplete: string = $ref('');
const router = useRouter();
const updateComponentsRef: number | undefined = $computed(() => props.updateComponents);
const autocompleteType: string = $computed(() => props.fieldValue === 'aatSubjects' ? 'subjects' : 'resources');

const getFieldHitLabels = (hits: string[]): string => {
  return (utils.getSorted((hits ?? []).map((field: string) => aggregationModule.getTitle(field)), '') ?? []).join(', ');
}

const getAutoCompleteLabel = (label: string, searchString: any) => {
  if (label) {
    return utils.getMarked(utils.sentenceCase(label), searchString);
  }
  return '*' + utils.escHtml(searchString) + '*';
}

const doAutocomplete = () => {
  const value = props.newSearch.trim().toLowerCase();

  if (!value || value.length < 2) {
    autocomplete = '';
    return;
  }

  let cached = searchModule.getAutoComplete[props.fieldValue + value];

  if (cached !== undefined) {
    autocomplete = cached.hits?.length ? cached : `No ${ autocompleteType } found..`;
    return;
  }

  autocomplete = `<i class="fas fa-circle-notch fa-spin mr-sm"></i> Loading ${ autocompleteType }..`;

  utils.debounce('autocompleteSearch', async () => {
    await searchModule.setAutocomplete({ type: props.fieldValue, q: value });
    let stored = searchModule.getAutoComplete[props.fieldValue + value];

    autocomplete = stored && stored.hits?.length ? stored : `No ${ autocompleteType } found..`;
  }, 300);
}

const getFilteredVariants = (variants: any) => {
  return utils.getSorted(variants?.filter((variant: any) => variant && !/^zh/.test(variant.lang)), 'label');
}

const autocompleteClick = (e: any, hit: any) => {
  autocomplete = '';

  if (autocompleteType === 'subjects') {
    if (!e.target.className || !e.target.className.includes('fas')) {
      const searchValues: any = {
        derivedSubject: hit.label,
      };

      if (!props.stayOnPage) {
        searchValues.path = '/search';
      }

      searchModule.setSearch(searchValues);

    } else {
      router.push(utils.paramsToString(`/subject/${ hit.id }`, { derivedSubject: hit.label }));
    }
  } else {
    router.push(`/resource/${ hit.id }`);
  }
}

watch($$(updateComponentsRef), () => {
  // on close
  if (props.updateComponents === -1) {
    autocomplete = '';
  }
  // on change
  else {
    doAutocomplete();
  }
});
</script>
