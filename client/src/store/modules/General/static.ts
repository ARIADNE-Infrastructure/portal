export const frontPageLinks = [
  {
    id: 'where',
    icon: 'fas fa-globe-americas',
  },
  {
    id: 'when',
    icon: 'fas fa-chart-line',
  },
  {
    id: 'what',
    icon: 'fas fa-bars',
  }
];

export const mainNavigation = [
  {
    'path': '/search',
    'name': 'Catalog',
    'icon': 'globe-americas',
    'color': 'yellow',
    'border': 'border-yellow',
    'bg': 'bg-yellow-20',
    'hover': 'hover:bg-yellow-20',
  },
  {
    'path': '/services',
    'name': 'Services',
    'icon': 'cog',
    'color': 'red',
    'border': 'border-red',
    'bg': 'bg-red-20',
    'hover': 'hover:bg-red-20',
  },
  {
    'path': '/about',
    'name': 'About',
    'icon': 'question-circle',
    'color': 'green',
    'border': 'border-green',
    'bg': 'bg-green-20',
    'hover': 'hover:bg-green-20',
  },
];

export const services = {
  'Frameworks': [
    {
      title: 'GeoPortal Framework',
      description: 'GeoPortal is a feature-complete framework enabling the publication, access and management of GIS projects consisting of multiple documents, images, and datasets. It can be configured with the XML specification of the GIS project data model.'
                   + '\n\nThe framework includes:\n'
                   + 'the Data Collection Form assisting users to publish GIS projects; a GIS Viewer allowing any user to visualize projects on a map; the Project Viewer assisting users to access all the information, documents, images and datasets associated to the GIS project; the GeoPortal service managing validation and management of GIS projects. The service relies on the D4Science Workspace for storing and accessing attached documents and on the D4Science SDI (Spatial Data Infrastructure) to offer OGC Compliant Services (e.g. WMS, WFS, WCS, etc.). It also uses an internal archive of registered documents for the management of Projects lifecycle and for querying purposes.',
      url: 'https://wiki.gcube-system.org/',
      img: 'geo-portale.png'
    }
  ],
  'Web Services': [
    {
      title: 'Visual Media Service',
      description: 'The ARIADNE Visual Media Service provides easy publication and presentation on the web of complex visual media assets. It is an automatic service that allows to upload visual media files on an ARIADNE server and to transform them into an efficient web format, making them ready for web-based visualization.',
      url: 'https://visual.ariadne-infrastructure.eu',
      img: 'visual-media-service.png'
    },
    {
      title: 'Landscape Factory',
      description: 'Landscape Services for ARIADNE are a set of responsive web services that include large terrain dataset generation, 3D landscape composing and 3D model processing, leveraging powerful open-source frameworks and toolkits such as GDAL, OSGjs, OpenSceneGraph and ownCloud. The main components include: the cloud service, the terrain generation service, the terrain gallery and the front-end web component for interactive visualization.',
      url: 'http://landscape.ariadne-infrastructure.eu/',
      img: 'landscape-services.png'
    },
    {
      title: 'DCCD: Digital Collaboratory for Cultural Dendrochronology',
      description: 'To improve European integration of dendrochronological data, DANS has now made it possible for others to use the same software as the DCCD-repository of DANS, and use existing components to create their own dendrochronological archive that is also ARIADNE compatible. This open source software is available from the following GitHub repository: https://github.com/DANS-KNAW/dccd-webui The DCCD software is an online digital archiving system for dendrochronological data. A recent version of this software (system) is deployed as "Digital Collaboratory for Cultural Dendrochronology" (DCCD) at http://dendro.dans.knaw.nl. More information about the Digital Collaboratory for Cultural Dendrochronology (DCCD) project can be found here: http://vkc.library.uu.nl/vkc/dendrochronology. The DCCD is the primary archaeological/historical tree-ring (meta)data network existing in Europe. It became operational in 2011. Within the DCCD Belgian, Danish, Dutch, German, Latvian, Polish, and Spanish laboratories have joined data in a manner that suits their shared and individual research agendas. In its present state the DCCD contains measurement series of different wood species derived from objects and sites dating between 6000 BC and present. All data sets are described with very detailed metadata according to the newly developed international dendrochronological data standard TRiDaS (Jansma et al. 2010). The collection is derived by research from archaeological sites (including old landscapes), shipwrecks, historical architecture and mobile heritage (e.g. paintings, furniture).',
      url: 'https://dendro.dans.knaw.nl/',
      img: 'dendro-service.png'
    },
    {
      title: 'IDAI.vocab',
      description: 'The new DAI Thesaurus of Archaeological Concepts was designed from the onset as a thesaurus of German words and phrases with significant multilingual support. The core of the thesaurus is a list of concepts related to the domain of archaeology (nouns, verbs, less frequently adjectives, but also complex phrases that point to a specific object, such as “carrarischer Marmor”) all linked to corresponding translations in a wide spectrum of different languages; we also established a minimal set of relations between the German terms (synonyms, direct hyper- and hyponyms), and grouped the equivalent terms together; whenever it is possible, we also resolved equivalent terms by selecting one preferred concept. In addition we connect terms and concepts by SKOS links to external thesauri, like the Arts & Architecture Thesaurus of the Getty Institution.',
      url: 'https://archwort.dainst.org/de/vocab/',
      img: 'idai-vocab-service.png'
    },
    {
      title: 'IDAI.gazetteer',
      description: 'The German Archaeological Institute together with the Cologne Digital Archaeology Laboratory is developing the iDAI.gazetteer - a web service connecting toponyms with coordinates. It was initially built as an authority file/controlled vocabulary for any geo-related information in information systems of the DAI. Furthermore it is meant to link these data with other worldwide gazetteer-systems.',
      url: 'https://gazetteer.dainst.org/app/#!/home',
      img: 'idai-gazetteer-service.png'
    },
    {
      title: 'Heritage Data: Linked Data Vocabularies for Cultural Heritage',
      description: 'National cultural heritage thesauri and vocabularies have acted as standards for use by both national organizations and local authority Historic Environment Records, but lacked the persistent Linked Open Data (LOD) URIs that would allow them to act as vocabulary hubs for the Web of Data. The AHRC funded SENESCHAL project made such vocabularies available online as Semantic Web resources with persistent URIs. Web services were developed by University of South Wales to make the vocabulary resources programmatically accessible and searchable.',
      url: 'https://www.heritagedata.org/blog/services/',
      img: 'heritage-data-service.png'
    },
    {
      title: 'Vocabulary matching tool',
      description: 'The Vocabulary Matching Tool developed by University of South Wales allows users to align Linked Data vocabulary concepts with Getty Art & Architecture Thesaurus concepts. The tool is a browser based application that presents concepts from chosen source and target vocabularies side by side, exposing additional contextual evidence to allow the user to make an informed choice when deciding on potential mappings (expressed as SKOS mapping relationships). The tool is intended for vocabularies already expressed in RDF/SKOS and can work directly with the data – querying external SPARQL endpoints rather than storing any local copies of complete vocabularies. The set of mappings developed can be saved locally, reloaded and exported to a number of different output formats. The tool is available for use at http://heritagedata.org/vocabularyMatchingTool and the source code is also available (open source) for local download and installation.',
      url: 'https://github.com/cbinding/VocabularyMatchingTool',
      img: 'vocabulary-matching-tool-service.png'
    }
  ],
  'Stand-alone Services': [
    {
      title: 'MeshLab',
      description: 'MeshLab is an open source, portable, and extensible system for the processing and editing of unstructured 3D triangular meshes. The system is aimed to help the processing of the typical not-so-small unstructured models arising in 3D scanning, providing a set of tools for editing, cleaning, healing, inspecting, rendering and converting this kind of meshes. In the archeoleogical field, MeshLab is strongly used by the community in the context of 3D reconstruction from scanning, mesh cleaning, mesh comparison and data presentation.',
      url: 'https://www.meshlab.net/',
      img: 'meshlab-service.png'
    },
    {
      title: 'ARIADNE subject mappings: Spreadsheet template and conversion tool',
      description: 'The spreadsheet template used in ARIADNE for collating mappings from local vocabularies to Getty AAT, together with the XSLT conversion of the spreadsheet data to JSON and NTriples formats for upload to ARIADNE. This is an alternate vocabulary mapping approach to the interactive Vocabulary Matching Tool, developed by University of South Wales for source vocabularies (including term lists) not yet expressed in RDF but available in tabular form. The tool comprises a spreadsheet template with example mappings, together with a corresponding XSLT transformation. The user completes the spreadsheet with the set of source and target (AAT) concepts. The XSLT transformation expresses the mappings in JSON and NTriples formats.',
      url: 'https://github.com/cbinding/ARIADNE-subject-mappings',
      img: 'ariadne-subject-mapping-spreadsheet-template.png'
    },
    {
      title: 'STELETO data conversion tool',
      description: 'STELETO is an open source, cross-platform application developed by University of South Wales to convert tabular data into other textual formats, via a custom template. It takes a delimited text file as input, applies the specified template and creates a text file as output. It was used in ARIADNE to convert the data used in the Wood/Dendrochronology data integration case study to a CIDOC CRM compatible RDF format. The source code is available (open source) for download and installation.',
      url: 'https://github.com/cbinding/STELETO/',
      img: 'steleto-data-conversion-tool.png'
    },
    {
      title: 'Demonstrator for ARIADNE Dendrochronology Data/NLP Integration Case Study',
      description: 'The Demonstrator is a SPARQL query builder for the Wood/Dendrochronology Case Study, developed by University of South Wales that seeks to hide the complexity of the underlying ontology. As the user selects from the interface, an underlying SPARQL query is automatically constructed in terms of the corresponding ontological entities. A set of interactive controls offer search and browsing of the extracted archaeological data. The controls are designed to be browser agnostic and the Demonstrator will run in most modern browsers. The experimental Web application cross searches over information extracted from datasets and text reports and expressed as CRM/AAT based RDF. Information from Dutch, English, Swedish archaeological reports was extracted by Natural Language Processing pipelines. Hierarchical expansion has been implemented over the Getty AAT and results from narrower concepts are included when available. The Demonstrator is available for use at http://ariadne-lod.isti.cnr.it and the source code is also available (open source) for local download and installation.',
      url: 'https://github.com/cbinding/ARIADNE-data-integration',
      img: 'ariadne-data-integration-case-study-demonstrator.png'
    },
    {
      title: 'English, Dutch, Swedish rule-based Natural Language Processing pipelines',
      description: 'Seven Named Entity Recognition (NER) pipelines for the text mining of English, Dutch and Swedish archaeological reports developed by University of South Wales. The pipelines run on the GATE (gate.ac.uk) open source platform and match a range of entities of archaeological interest such as Physical Objects, Materials, Structure Elements, Dates, etc. The pipelines have been used in ARIADNE data integration case studies. There are Dutch, English, Swedish general archaeological pipelines (for the above entities), experimental Dutch, English, Swedish specific pipelines for entities of dendrochronology interest, such as architectural elements, wood materials and dates, and a specific pipeline targeted at English entities of numismatic interest, such as coins, denomination, material and date. The English language pipelines build on previous work, while the Dutch and Swedish pipelines are more exploratory (see ARIADNE Deliverable D16.4 for details).',
      url: 'https://github.com/avlachid/Multilingual-NLP-for-Archaeological-Reports-Ariadne-Infrastructure',
      img: 'english-etc-nlp.png'
    },
  ],
  'Institutional Services': [
    {
      title: 'Archaeology Data Service',
      description: 'The Archaeology Data Service is the national digital data archive for archaeology the UK and a world-leading data management centre for the archaeology and heritage sector. ADS-Easy provides an online costing tool and data deposit service. The ADS operates according to the OAIS model for digital archives and holds the Data Seal of Approval, the internationally recognized quality mark for trusted digital repositories. In 2012 the ADS was awarded the Digital Preservation Coalition’s Decennial Award for the most outstanding contribution to digital preservation of the last decade.',
      url: 'https://archaeologydataservice.ac.uk/',
      img: 'arch-data-service.png'
    },
    {
      title: 'DANS: Data Archiving and Networked Services',
      description: 'The e-depot for Dutch archaeology is accommodated at DANS, the national digital research data archive for the Netherlands. A wealth of digital archaeological excavation data such as maps, field drawings, photographs, tables and publications is accessible via EASY, DANS’ online archiving (deposit, preservation and reuse) service. DANS operates according to the OAIS model for digital archives and holds the Data Seal of Approval, the internationally recognized quality mark for trusted digital repositories. DANS was established in 2005, with predecessors dating back to 1964, and now comprises some 45 staff. DANS\'s activities are centred around 3 core services: data archiving, data reuse, training and consultancy. Driven by data, DANS ensures the further improvement of sustained access to digital research data with its services and participation in (inter)national projects and networks. DANS is an institute of the Royal Netherlands Academy of Arts and Sciences (KNAW) and co-founded by the Netherlands Organization for Scientific Research (NW0).',
      url: 'https://dans.knaw.nl/nl',
      img: 'dans-service.png'
    },
    {
      title: 'ARACHNE',
      description: 'Arachne is the central Object database of the German Archaeological Institute (DAI) and the Archaeological Institute of the University of Cologne. Arachne is intended to provide archaeologists and Classicists with a free internet research tool for quickly searching hundreds of thousands of records on objects and their attributes. It provides an online data deposit service. This combines an ongoing process of digitizing traditional documentation (stored on media which are both threatened by decay and largely unexplored) with the production of new digital object and graphic data. Wherever possible, Arachne follows a paradigm of highly structurized object-metadata which is mapped onto the CIDOC-CRM, to address machine-readable metadata strategies of the Semantic Web. This “structured world” of Arachne requires large efforts in time and money and is therefore only possible for privileged areas of data. While there is an ever-increasing range of new, “born digital” data, in reality only a small effort-per-object ratio can be applied. It therefore requires a “low-threshold” processing structure which is located in the “unstructured world” of Arachne. All digital (graphic and textual) information is secure on a Tivoli Storage System (featuring long-term multiple redundandancy) and distributed online through the Storage Area Network in Cologne via AFS.',
      url: 'https://arachne.dainst.org/',
      img: 'arachne-service.png'
    },
  ],
  'Services for Humans': [
    {
      title: 'Thesaurus RA - Strumenti terminologici Scheda RA Reperti Archeologici',
      description: 'The RA terminological tool, curated by ICCU and VAST-LAB, constitutes a reworked version of the RA Thesaurus issued by the ICCD. The RA Thesaurus provides all the necessary terminological facilities for an efficent and well structured recording of the object coming from archaeological excavations. The vocabulary has been implemented by ICCD to support the encoding of two specific fields (OGTD - CLS). These two fields describe the definition of the object and its class and production.',
      url: 'https://vast-lab.org/thesaurus/ra/vocab/',
      img: 'thesaurus-ra-service.png'
    }
  ],
};
