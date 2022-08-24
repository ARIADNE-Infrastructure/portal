<template>
  <help-tooltip
    top="-2.5rem"
    left="0"
    messageClasses="bg-yellow text-white cursor-pointer"
    @click.prevent="showPopup = true">
    <div class="text-white cursor-pointer">
      <i class="fas fa-question-circle text-lg" />
    </div>
    <template v-slot:content>
      <div>
        Show search help
      </div>
    </template>
  </help-tooltip>

  <div class="fixed bg-black-60 text-hg w-full h-full top-0 left-0 flex items-center justify-center transition-all duration-300"
    :class="showPopup ? 'z-40 opacity-1 visible' : 'z-neg30 opacity-0 invisible'"
    @click.self="showPopup = false"
  >
    <div class="bg-white text-hg max-w-screen-lg max-h-full overflow-y-auto">
      <h2 class="text-xl mb-md bg-yellow text-white px-xl py-md flex items-center justify-between">
        <div>
          Search help
          <i class="fas fa-question-circle text-lg ml-sm" />
        </div>
        <i class="fas fa-times text-xl cursor-pointer pl-sm py-sm hover:text-red transition-color duration-300" @click="showPopup = false"></i>
      </h2>
      <div class="px-xl py-md">
        <p class="mb-md">Welcome to the ARIADNE Portal where you can search over 2 million records relating to archaeological fieldwork reports, research papers, scientific datasets, sites and monuments through to individual artefacts from across the world.</p>

        <p class="mb-md">The search is designed to start with one or more key words where logical “AND” is applied. For example, a search for “gold coin” will return only records where both these words are to be found in the metadata record. The default setting is “All fields”, i.e. the whole record is searched. There are several filters on the Results page which can be applied to further refine the returned results.</p>

        <p class="mb-md">Alternatively, there are broad filters available here for a more targeted search:</p>
        <ul class="list-disc pl-lg mb-lg">
          <li>Time period - name of time period(s) e.g. Iron age, medieval...</li>
          <li>Place - place name which can be anything from a specific location to a continent.</li>
          <li>Title - only the title fields are searched for matching terms.</li>
          <li>
            Getty Arts and Architecture Thesaurus subject - the subject(s) defined by the data provider are matched to the Getty AAT which enables the end user to see and search for data with matching translations and also related terms. More on the Getty AAT:&nbsp;
            <b-link
              target="_blank"
              href="https://www.getty.edu/research/tools/vocabularies/aat/about.html"
              class="word-break"
              :useDefaultStyle="true">
              https://www.getty.edu/research/tools/vocabularies/aat/about.html
            </b-link>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { $ref } from 'vue/macros';
import HelpTooltip from '@/component/Help/Tooltip.vue';
import BLink from '@/component/Base/Link.vue';

let showPopup: boolean = $ref(false);
</script>
