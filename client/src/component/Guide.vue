<template>
	<div class="pt-3x px-base max-w-screen-xl mx-auto text-mmd">
    <h1 class="text-2xl mb-md">ARIADNE portal guide</h1>
    <p class="mb-md">The portal offers users a simple friendly interface. It includes the familiar search box, which, once users start typing, suggests keywords based on the content that is actually available from ARIADNE. Typing the single letter “f” offers the option of searching for fortifications, flat-bottomed watercraft and fragments. The magic of this search technology is that it accesses the multi-lingual vocabularies. Typing the letter “f” offers the option of searching for 'blanks”, which are 'flan' in Spanish and 'Halbfabrikat' in German.</p>
    <p class="mb-3x">The keyword search is a very quick and easy way of starting to use the ARIADNE portal. For users who would like to browse the catalogue to see what type of content is available there are three starting points: Where, When and What.</p>

    <img :src="`${ assets }/about/browse.png`" alt="browse icons" class="mb-lg" :width="imgMaxWidth">
    <h2 class="text-lg mb-md">Browse what, where &amp; when</h2>
    <p class="mb-3x">Choosing to begin exploring ARIADNE by clicking on “What” is a very good way of getting an idea of the overall semantic scope of the collection. This browse option displays a word cloud based on the subject concepts included in the content metadata.</p>

    <img :src="`${ assets }/about/cloud.png`" alt="tag cloud" class="mb-lg" :width="imgMaxWidth">
    <h2 class="text-lg mb-md">Most common words in the word cloud</h2>
    <p class="mb-md">Houses, earthworks, archaeological sites and buildings are all well represented in the collection. Archaeologists will notice many other familiar features to choose to continue their exploration of the collection.</p>
    <p class="mb-3x">Clicking on any of the subject concepts in the Word Cloud takes you to a list of the results.</p>

    <img :src="`${ assets }/about/search.png`" alt="search page" class="mb-lg" :width="imgMaxWidth">
    <h2 class="text-lg mb-md">Search the catalogue</h2>
    <p class="mb-md">All three browse options (what, where and when) are offered as additional filters on the search results page. The figure below shows the spread of content relating to “mounds” across Europe in an arc from the north of Scotland to Romania mainly concentrated in the period around 1000 BP.</p>
    <p class="mb-md">This type of visualisation is a great way of helping users to explore the catalogue. Researchers can use the filters to narrow their searches to a particular region or time period, and the visualisation to identify new areas that may be related to their research interests.</p>
    <p class="mb-3x">The search results provide a list of the datasets that are available. More detailed information is available for each dataset, which includes a description and a link to the content if it is available on the web.</p>

    <img :src="`${ assets }/about/resource.png`" alt="resource page" class="mb-lg" :width="imgMaxWidth">
    <h2 class="text-lg mb-md">Resource pages</h2>
    <p class="mb-base">The listing encourages researchers to explore the catalogue by highlighting other content in the same geographic region or related to the same subject topic.</p>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import { generalModule } from "@/store/modules";
import BLink from '@/component/Base/Link.vue';

@Component({
  components: {
    BLink,
  }
})
export default class Guide extends Vue {
  imgMaxWidth: number = 800;

  get assets(): string {
    return generalModule.getAssetsDir;
  }
}
</script>
