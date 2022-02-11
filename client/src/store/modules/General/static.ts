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
    'name': 'Catalogue',
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
  'Web Services': [
    {
      title: 'Visual Media Service',
      description: 'Visual Media Service provides easy publication on the web for complex visual media assets: images, relightable images and 3D models. The assets are uploaded, converted into a multiresolution, compressed format optimized for streaming on the web.</br>The presentation can be customized and the resulting assets can be either downloaded or accessed directly in the Visual Media Service.',
      url: 'https://visual.ariadne-infrastructure.eu',
      img: 'visual-media-service.png'
    },
    {
      title: 'Landscape Services',
      description: 'Landscape Services for ARIADNE are a set of responsive web services that include large terrain dataset generation, 3D landscape composing and 3D model processing, leveraging powerful open-source frameworks and toolkits such as GDAL, OSGjs, OpenSceneGraph and ownCloud. The main components include: the cloud service, the terrain generation service, the terrain gallery and the front-end web component for interactive visualization.',
      url: 'http://landscape.ariadne-infrastructure.eu/',
      img: 'landscape-services.png'
    },
    {
      title: 'DCCD: Digital Collaboratory for Cultural Dendrochronology',
      description: 'The DCCD is the primary archaeological/historical tree-ring (meta)data network existing in Europe. It became operational in 2011.  Measurement series of different wood species derived from objects and sites dating between 6000 BC and present are made available. All data sets are described with very detailed metadata according to the newly developed international dendrochronological data standard TRiDaS (Jansma et al. 2010). The collection is derived by research from archaeological sites (including old landscapes), shipwrecks, historical architecture and mobile heritage (e.g. paintings, furniture). Since 2015, it was possible for others to create their own dendrochronological archive: <a href=\"https://github.com/DANS-KNAW/dccd-webui\">GitHub - DANS-KNAW/dccd-webui: DCCD Web-UI</a>. In 2021, technical developments have led to a new repository: DataverseNL is now the new platform for depositing, downloading and disseminating the DCCD tree-ring data.',
      url: 'https://dataverse.nl/dataverse/dccd',
      img: 'dendro-service.png'
    },
    {
      title: 'Heritage Data: Linked Data Vocabularies for Cultural Heritage',
      description: 'National cultural heritage thesauri and vocabularies have acted as standards for use by both national organizations and local authority Historic Environment Records, but lacked the persistent Linked Open Data (LOD) URIs that would allow them to act as vocabulary hubs for the Web of Data. The AHRC funded SENESCHAL project made such vocabularies available online as Semantic Web resources with persistent URIs. Web services were developed by University of South Wales to make the vocabulary resources programmatically accessible and searchable.',
      url: 'https://www.heritagedata.org/blog/services/',
      img: 'heritage-data-service.png'
    },
    {
      title: 'Vocabulary matching tool',
      description: 'The Vocabulary Matching Tool developed by University of South Wales allows users to align Linked Data vocabulary concepts with Getty Art & Architecture Thesaurus concepts. The tool is a browser based application that presents concepts from chosen source and target vocabularies side by side, exposing additional contextual evidence to allow the user to make an informed choice when deciding on potential mappings (expressed as SKOS mapping relationships). The tool is intended for vocabularies already expressed in RDF/SKOS and can work directly with the data – querying external SPARQL endpoints rather than storing any local copies of complete vocabularies. The set of mappings developed can be saved locally, reloaded and exported to a number of different output formats. The tool is available for use at <a href=\"http://heritagedata.org/vocabularyMatchingTool">http://heritagedata.org/vocabularyMatchingTool</a> and the source code is also available (open source) for local download and installation.',
      url: 'https://vmt.ariadne.d4science.org/vmt/vmt-app.html',
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
  'Services for Humans': [
    {
      title: 'EpHEMERA',
      description: 'The EpHEMERA service, developed by the Cyprus Institute, is a platform allowing users to visualize in 3D the layers of archaeological excavations, ancient buildings, archaeological areas, and their related documentation. Specifically, the primary objective of the service is the documentation and management of Cultural Heritage at risk (e.g., invasive urban development, war conflicts, natural and human agents, inaccessibility). The 3D interactive online geo-service is aimed at the preservation of endangered architectural and archaeological heritage. The system is intended to serve as infrastructure where it is possible to visualize online and through standard web browser 3D architectural and archaeological models under risk, query the database system and retrieve metadata attached to every single virtual object; extract geometric and morphological information.'
        + '\n\n<b>Requirements/needs:</b>'
        + '\n- Characteristics of the 3D models:'
        + '<ul style="margin-left: 1rem">'
          + '<li>Point cloud (coloured or not)</li>'
          + '<li>Data quality (resolution)</li>'
          + '<li>Geo-referencing (yes or not)</li>'
          + '<li>Images gallery (yes or not)</li>',
      url: 'http://ephemera.cyi.ac.cy/?q=collection',
      img: 'ephemera.png'
    },
    {
      title: 'Thesaurus RA - Strumenti terminologici Scheda RA Reperti Archeologici',
      description: 'RA Thesaurus - A terminological tool for the compilation of RA ("Archaeological Find”) forms of the Italian Ministry of Cultural Heritage.<br/><br/>The RA terminological tool, developed by ICCU (Italian Central Institute for Unique Catalogue) and PIN, is an online facility for Italian archaeologists, providing a reworked version of the RA Thesaurus issued by ICCD, the Italian Central Institute for Documentation and Standards of the Italian Ministry of Cultural Heritage (MiBAC). The RA Thesaurus contains all the necessary terminological facilities for an efficient and well structured recording of the artefacts coming from Italian archaeological excavations. The vocabulary has been implemented by ICCD to support the encoding of two specific fields (OGTD - CLS) of MiBAC\'s official “Archaeological Find” (RA) form. These two fields are intended to describe the definition of the archaeological objects and their classes of production.',
      url: 'https://vast-lab.org/thesaurus/ra/vocab/',
      img: 'thesaurus-ra-service.png'
    }
  ],
  'Frameworks': [
    {
      title: 'GeoPortal Framework',
      description: 'GeoPortal is a feature-complete framework enabling the publication, access and management of GIS projects consisting of multiple documents, images, and datasets. It can be configured with the XML specification of the GIS project data model.'
                   + '\n\nThe framework includes:\n'
                   + 'the Data Collection Form assisting users to publish GIS projects; a GIS Viewer allowing any user to visualize projects on a map; the Project Viewer assisting users to access all the information, documents, images and datasets associated to the GIS project; the GeoPortal service managing validation and management of GIS projects. The service relies on the D4Science Workspace for storing and accessing attached documents and on the D4Science SDI (Spatial Data Infrastructure) to offer OGC Compliant Services (e.g. WMS, WFS, WCS, etc.). It also uses an internal archive of registered documents for the management of Projects lifecycle and for querying purposes.',
      url: 'https://wiki.gcube-system.org/',
      img: 'geo-portale.png'
    }
  ]  
};
