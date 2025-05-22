export const titles = {
  ariadneSubject: 'Resource Type',
  spatial: 'Place',
  nativeSubject: 'Original Subject',
  derivedSubject: 'Getty AAT Subject',
  temporal: 'Dating',
  aatSubjects: 'Getty AAT Subject',
  accessPolicy: 'Access Policy',
  accessRights: 'Access Rights',
  accrualPeriodicity: 'Accrual Periodicity',
  audience: 'Audince',
  contactPoint: 'Contact Point',
  contributor: 'Contributor',
  country: 'Country',
  dataType: 'Data Type',
  creator: 'Creator',
  description: 'Description',
  distribution: 'Distribution',
  extent: 'Extent',
  hasItemMetadataStructure: 'Metadata Structure',
  hasMetadataRecord: 'Metadata Record',
  identifier: 'Identifier',
  isPartOf: 'Part Of',
  issued: 'Issued',
  keyword: 'Keyword',
  landingPage: 'Landing Page',
  language: 'Language',
  legalResponsible: 'Legal Responsible',
  modified: 'Last updated',
  originalId: 'Original ID',
  owner: 'Owner',
  packageId: 'Package ID',
  placeName: 'Place Name',
  postcode: 'Postcode',
  providerId: 'Provider ID',
  publisher: 'Publisher',
  rdfAbout: 'RDF About',
  resourceType: 'Category',
  rights: 'Rights',
  scientificResponsible: 'Scientific Responsible',
  technicalResponsible: 'Technical Responsible',
  title: 'Title',
};

export const descriptions = {
  ariadneSubject: 'Broad resource type',
  nativeSubject: 'Subject defined by data provider',
  derivedSubject: 'Getty AAT Subject',
  temporal: 'Numerical date or date range, period name(s',
  publisher: 'Data provider',
  contributor: 'Organisation or individual contributing the resource',
  country: 'Country for the resource',
  dataType: 'Data Type for the resource',
  aatSubjects: 'Subject type classified according to the Getty Art and Architecture thesaurus',
}

// temporary separate titles for results
export const resultTitles = {
  ariadneSubject: 'Resource Type',
  spatial: 'Place',
  nativeSubject: 'Original Subject',
  derivedSubject: 'Getty AAT Subject',
  temporal: 'Dating',
  aatSubjects: 'Getty AAT Subject',
  resourceType: 'Category',
};

export const types = [
  {
    id: 'ariadneSubject',
    prop: 'prefLabel',
    always: true
  },
  {
    id: 'spatial',
    prop: 'placeName',
    sorted: true,
    always: true
  },
  {
    id: 'publisher',
    prop: 'name',
    always: true,
    unformatted: true
  },
  {
    id: 'nativeSubject',
    prop: 'prefLabel',
    sorted: true,
    always: true
  },
  {
    id: 'derivedSubject',
    prop: 'prefLabel',
    sorted: true
  },
  {
    id: 'keyword'
  },
  {
    id: 'resourceType'
  },
  {
    id: 'contributor',
    prop: 'name',
    unformatted: true
  },
  {
    id: 'country',
    prop: 'name',
    unformatted: true,
    sorted: true
  },
  {
    id: 'dataType',
    prop: 'label',
    sorted: true
  },
  {
    id: 'temporal',
    prop: 'periodName'
  },
  {
    id: 'aatSubjects',
    prop: 'label',
    sorted: true
  },
];

