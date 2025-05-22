<template>
	<div class="pt-3x px-base max-w-screen-xl mx-auto text-mmd">
    <h1 class="text-2xl mb-md">About ARIADNE</h1>
    <p class="mb-md">ARIADNE is a research infrastructure for archaeology. Its main objective is to support research, learning and teaching by enabling access to digital resources and innovative new services. It does this by maintaining a catalogue of digital datasets, by promoting best practices in the management and use of digital data in archaeology, by offering training and advice, and by supporting the development of innovative new services for archaeology.</p>
    <p class="mb-3x">The datasets that are registered in the ARIADNE catalogue are held both by its partners and Associate partners and have been created through research, in excavations, in fieldwork, laboratory and other projects. In recent years archaeologists have been making increasing use of sophisticated digital equipment and techniques. During the course of a research project large volumes of data are created and collected, and become part of the research archive. ARIADNE aims to make these archives available through its portal for researchers to consult when starting new research.</p>

    <h2 class="text-lg mb-md">ARIADNE portal</h2>
    <p class="mb-md">The ARIADNE Portal offers a central point of access to the archaeological resources made available from partner institutions throughout Europe. Behind the portal lie the ARIADNE registry and a set of services that are used to manage information about the datasets, collections, vocabularies, metadata schemas and mappings.</p>
    <p class="mb-3x">The registry is used to gather information about data resources and services, and to support the search functionalities offered by the portal. Partners provide a description of their digital resources based on the ARIADNE AO-Cat which is used to include the resource in the registry. This data model was developed in the ARIADNEplus project following on from the experience gained from the Catalogue Data Model (ACDM) developed in the first ARIADNE project (2014-17). The AO-Cat covers a much greater range of data types enabling the inclusion of several different datasets including scientific data, inscriptions and hierarchical burial data.</p>

    <h2 class="text-lg mb-md">Disclaimer</h2>
    <p class="mb-md">Whilst every care has been taken in preparing the information published via the ARIADNE portal, the ARIADNE partners do not guarantee the accuracy or currency of the content. The consortium provides all material on an "as is" and "as available" basis. To the fullest extent permitted by law, the ARIADNE consortium excludes all representations, warranties, or conditions (whether expressed or implied) as to the comprehensiveness, accuracy, reliability, or otherwise of all material available via the portal. For further information about the material available via the portal, including its currency, users are advised to contact the data publisher.</p>
    <p class="mb-md">The ARIADNE consortium cannot be held responsible for any errors or omissions and accepts no liability whatsoever for any loss or damage howsoever arising.</p>
    <p class="mb-md">This website includes links to third party websites. These links are used to provide further information and are not intended to signify that the ARIADNE consortium endorses such websites and/or their content. The ARIADNE consortium takes no responsibility for any loss or damage suffered as a result of using information published on any of the pages of the linked websites.</p>
    <p class="mb-3x">Third party material located through the use of the ARIADNE portal may be subject to specific use constraints. It is the responsibility of users to be aware of such constraints and to abide by them.</p>

    <h2 class="text-lg mb-md">Guide</h2>
    <p class="mb-3x">
      Have a look at the
      <b-link href="https://ariadne-infrastructure.github.io/docs/Portal_User_Guide/Section-1_What" target="_blank" :useDefaultStyle="true">
        ARIADNE portal guide
      </b-link>
      for information and help on how to search and navigate the portal or download the PDF version which is far more detailed and contains some examples.
    </p>

    <h2 class="text-lg mb-md">For more information</h2>
    <p class="mb-base">
      About the ARIADNE research infrastructure please see our project website:
      <b-link
        target="_blank"
        href="https://www.ariadne-research-infrastructure.eu"
        class="word-break"
        :useDefaultStyle="true"
      >https://www.ariadne-research-infrastructure.eu</b-link>.
    </p>
  </div>
</template>

<script setup lang="ts">
import BLink from '@/component/Base/Link.vue';
</script>
