<!-- Front page -->
<template>
	<div>
    <h1 class="text-2x mb-md">About ARIADNE</h1>
    <p class="mb-md">ARIADNE is a research infrastructure for archaeology. Its main objective is to support research, learning and teaching by enabling access to digital resources and innovative new services. It does this by maintaining a catalogue of digital datasets, by promoting best practices in the management and use of digital data in archaeology, by offering training and advice, and by supporting the development of innovative new services for archaeology.</p>
    <p class="mb-3x">The datasets that are registered in the ARIADNE catalogue are held by its partners and have been created through research, in excavations, in fieldwork, laboratory and other projects. In recent years archaeologists have been making increasing use of sophisticated digital equipment and techniques. During the course of a research project large volumes of data are created and collected, and become part of the research archive. ARIADNE aims to make these archives available through its portal for researchers to consult when starting new research.</p>

    <h2 class="text-xl mb-md">ARIADNE portal</h2>
    <p class="mb-md">The ARIADNE Portal offers a central point of access to the archaeological resources made available from partner institutions throughout Europe. Behind the portal lie the ARIADNE registry and a set of services that are used to manage information about the datasets, collections, vocabularies, metadata schemas and mappings.</p>
    <p class="mb-3x">The registry is used to gather information about data resources and services, and to support the search functionalities offered by the portal. Partners provide a description of their digital resources based on the ARIADNE Catalog Data Model (ACDM), which is used to include the resource in the registry.</p>

    <h2 class="text-xl mb-md">Disclaimer</h2>
    <p class="mb-md">Whilst we have used best efforts to ensure the accuracy of resources catalogued by the ARIADNE portal, we explicitly disclaim to the extent permitted by law any responsibility for the accuracy, content, or availability of information located through use of the Portal, or for any damage incurred owing to use of the information contained therein.</p>
    <p class="mb-md">Information located through use of the Portal may be subject to specific use constraints, as advised by the data publishers. It is the responsibility of potential and actual users to be aware of such constraints and to abide by them. By making use of material on this web server, including the contents of the Portal, you accept these provisions.</p>
    <p class="mb-3x">If you notice a mistake in one of the catalogue records or linked resources, then comments should be addressed to the data publisher, not to the ARIADNE consortium.</p>

    <h2 class="text-xl mb-md">For more information</h2>
    <p class="mb-3x">About the ARIADNE research infrastructure please see our project website: <a target="_blank" href="https://www.ariadne-infrastructure.eu" class="break-word" :class="utils.linkClasses()">https://www.ariadne-infrastructure.eu</a>.</p>

    <h2 class="text-xl mb-md">Guide to searching on the ARIADNE portal</h2>
    <p class="mb-md">The portal offers users a simple friendly interface. It includes the familiar search box, which, once users start typing, suggests keywords based on the content that is actually available from ARIADNE. Typing the single letter “f” offers the option of searching for fortifications, flat-bottomed watercraft and fragments. The magic of this search technology is that it accesses the multi-lingual vocabularies. Typing the letter “f” offers the option of searching for 'blanks”, which are 'flan' in Spanish and 'Halbfabrikat' in German.</p>
    <p class="mb-3x">The keyword search is a very quick and easy way of starting to use the ARIADNE portal. For users who would like to browse the catalogue to see what type of content is available there are three starting points: Where, When and What.</p>

    <h2 class="text-xl mb-lg">Browse what, where &amp; when</h2>
    <img :src="`${ assets }/about/browse.png`" alt="browse icons" class="mb-lg" :width="imgMaxWidth">
    <p class="mb-3x">Choosing to begin exploring ARIADNE by clicking on “What” is a very good way of getting an idea of the overall semantic scope of the collection. This browse option displays a word cloud based on the subject concepts included in the content metadata.</p>

    <h2 class="text-xl mb-md">Most common words in the word cloud</h2>
    <img :src="`${ assets }/about/cloud.png`" alt="tag cloud" class="mb-lg" :width="imgMaxWidth">
    <p class="mb-md">Houses, earthworks, archaeological sites and buildings are all well represented in the collection. Archaeologists will notice many other familiar features to choose to continue their exploration of the collection.</p>
    <p class="mb-3x">Clicking on any of the subject concepts in the Word Cloud takes you to a list of the results.</p>

    <h2 class="text-xl mb-lg">Search the catalogue</h2>
    <img :src="`${ assets }/about/search.png`" alt="search page" class="mb-lg" :width="imgMaxWidth">
    <p class="mb-md">All three browse options (what, where and when) are offered as additional filters on the search results page. The figure below shows the spread of content relating to “mounds” across Europe in an arc from the north of Scotland to Romania mainly concentrated in the period around 1000 BP.</p>
    <p class="mb-md">This type of visualisation is a great way of helping users to explore the catalogue. Researchers can use the filters to narrow their searches to a particular region or time period, and the visualisation to identify new areas that may be related to their research interests.</p>
    <p class="mb-3x">The search results provide a list of the datasets that are available. More detailed information is available for each dataset, which includes a description and a link to the content if it is available on the web.</p>

    <h2 class="text-xl mb-md">Resource pages</h2>
    <img :src="`${ assets }/about/resource.png`" alt="resource page" class="mb-lg" :width="imgMaxWidth">
    <p class="mb-3x">The listing encourages researchers to explore the catalogue by highlighting other content in the same geographic region or related to the same subject topic.</p>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator';
import utils from '../utils/utils';

@Component
export default class About extends Vue {
  imgMaxWidth: number = 1000;
  utils = utils;

  get assets () {
    return this.$store.getters.assets();
  }
}
</script>
