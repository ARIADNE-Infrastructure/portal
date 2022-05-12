export const titles = {
  ariadneSubject: 'Resource type',
  spatial: 'Place',
  nativeSubject: 'Original subject',
  derivedSubject: 'Getty AAT Subjects',
  temporal: 'Dating',
  aatSubjects: 'Getty AAT Subjects',
  accessPolicy: 'Access Policy',
  accessRights: 'Access Rights',
  accrualPeriodicity: 'Accrual Periodicity',
  audience: 'Audince',
  contactPoint: 'Contact Point',
  contributor: 'Contributor',
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
  resourceType: 'Resurce Type',
  rights: 'Rights',
  scientificResponsible: 'Scientific Responsible',
  technicalResponsible: 'Technical Responsible',
  title: 'Title',
};

export const descriptions = {
  ariadneSubject: 'Broad resource type',
  nativeSubject: 'Subject defined by data provider',
  derivedSubject: 'Getty AAT Subjects',
  temporal: 'Numerical date or date range, period name(s',
  publisher: 'Data provider',
  contributor: 'Organisation or individual contributing the resource',
  aatSubjects: 'Subject type classified according to the Getty Art and Architecture thesaurus'
}

// temporary separate titles for results
export const resultTitles = {
  ariadneSubject: 'Resource type',
  spatial: 'Place',
  nativeSubject: 'Original subject',
  derivedSubject: 'Getty AAT Subjects',
  temporal: 'Dating',
  aatSubjects: 'Getty AAT Subjects'
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
    always: true
  },
  {
    id: 'derivedSubject',
    prop: 'prefLabel'
  },
  {
    id: 'keyword'
  },
  {
    id: 'contributor',
    prop: 'name',
    unformatted: true
  },
  {
    id: 'temporal',
    prop: 'periodName'
  },
  {
    id: 'aatSubjects',
    param: 'derivedSubjectIdLabel',
    prop: 'label'
  }
];

