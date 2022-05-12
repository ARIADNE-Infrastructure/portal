<template>
	<div class="pt-3x px-base max-w-screen-xl mx-auto text-mmd">
    <h1 class="text-2xl mb-md">About ARIADNE</h1>
    <p class="mb-md">ARIADNE is a research infrastructure for archaeology. Its main objective is to support research, learning and teaching by enabling access to digital resources and innovative new services. It does this by maintaining a catalogue of digital datasets, by promoting best practices in the management and use of digital data in archaeology, by offering training and advice, and by supporting the development of innovative new services for archaeology.</p>
    <p class="mb-3x">The datasets that are registered in the ARIADNE catalogue are held by its partners and have been created through research, in excavations, in fieldwork, laboratory and other projects. In recent years archaeologists have been making increasing use of sophisticated digital equipment and techniques. During the course of a research project large volumes of data are created and collected, and become part of the research archive. ARIADNE aims to make these archives available through its portal for researchers to consult when starting new research.</p>

    <h2 class="text-lg mb-md">ARIADNE portal</h2>
    <p class="mb-md">The ARIADNE Portal offers a central point of access to the archaeological resources made available from partner institutions throughout Europe. Behind the portal lie the ARIADNE registry and a set of services that are used to manage information about the datasets, collections, vocabularies, metadata schemas and mappings.</p>
    <p class="mb-3x">The registry is used to gather information about data resources and services, and to support the search functionalities offered by the portal. Partners provide a description of their digital resources based on the ARIADNE Catalog Data Model (ACDM), which is used to include the resource in the registry.</p>

    <h2 class="text-lg mb-md">Disclaimer</h2>
    <p class="mb-md">Whilst we have used best efforts to ensure the accuracy of resources catalogued by the ARIADNE portal, we explicitly disclaim to the extent permitted by law any responsibility for the accuracy, content, or availability of information located through use of the Portal, or for any damage incurred owing to use of the information contained therein.</p>
    <p class="mb-md">Information located through use of the Portal may be subject to specific use constraints, as advised by the data publishers. It is the responsibility of potential and actual users to be aware of such constraints and to abide by them. By making use of material on this web server, including the contents of the Portal, you accept these provisions.</p>
    <p class="mb-3x">If you notice a mistake in one of the catalogue records or linked resources, then comments should be addressed to the data publisher, not to the ARIADNE consortium.</p>

    <h2 class="text-lg mb-md">Guide</h2>
    <p class="mb-3x">
      Have a look at the
      <b-link to="/guide" :useDefaultStyle="true">
        ARIADNE portal guide
      </b-link>
      for information and help on how to search and navigate the portal.
    </p>

    <h2 class="text-lg mb-md">For more information</h2>
    <p class="mb-base">
      About the ARIADNE research infrastructure please see our project website:
      <b-link
        target="_blank"
        href="https://www.ariadne-infrastructure.eu"
        class="word-break"
        :useDefaultStyle="true"
      >
        https://www.ariadne-infrastructure.eu
      </b-link>
    .</p>
  </div>
</template>

<script setup lang="ts">
import BLink from '@/component/Base/Link.vue';
</script>
