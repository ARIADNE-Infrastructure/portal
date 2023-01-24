export const fields = [
  { val: '', text: 'All fields', group: 'default' },
  { val: 'time', text: 'Time period', group: 'default' },
  { val: 'location', text: 'Place', group: 'default' },
  { val: 'title', text: 'Title', group: 'default' },

  // subject
  { val: 'aatSubjects', text: 'Getty AAT Subject', group: 'subject' },
];

export const types = {
  1: 'excavation',
  2: 'event',
  3: 'site',
  4: 'scientific',
  5: 'artefact',
  6: 'burial',

  // new
  7: 'dating',
  8: 'survey',
  9: 'remote',
  10: 'structure',
  11: 'maritime',
  12: 'inscription',

  /* ISSUE: 19956
  https://support.d4science.org/issues/19956

  Site/monument > 10
  Fieldwork > 11
  Fieldwork report > 12
  Scientific analysis > 13
  Date > 14
  Artefact > 15
  Fieldwork archive > 16
  Inscription > 17
  Burial > 18
  Rock Art > 19
*/

};

export const validFromPaths = [
  { path: '/search', title: 'search results' },
  { path: '/publisher', title: 'publisher' },
  { path: '/browse/where', title: 'browse where' },
  { path: '/browse/when', title: 'browse when' },
  { path: '/browse/what', title: 'browse what' },
];

export const typesTemporary = {
  'Artefact': 'artefact',
  'Building survey': 'buildingsurvey',
  'Burial': 'burial',
  'Coin': 'coin',
  'Dating': 'dating',
  'Fieldwork': 'fieldwork',
  'Fieldwork archive': 'fieldworkarchive',
  'Fieldwork report': 'fieldworkreport',
  'Inscription': 'inscription',
  'Maritime': 'maritime',
  'Remote': 'remote', // to be added to data..
  'Rock Art': 'rockart',
  'Scientific analysis': 'scientificanalysis',
  'Site/monument': 'sitemonument',
  'Thematic Survey (http://purl.org/heritagedata/schemes/agl_et/concepts/147326)': 'thematicsurvey', // naming to be fixed..
};

export const thematicals = {
  '': 'Subject & Time period',
  'title': 'Title',
  'location': 'Location',
  'subject': 'Subject',
  'temporal': 'Time period',
};

export const ctsCertified = [
  'Archaeology Data Service',
  'Data Archiving and Networked Services (DANS)',
  'Swedish National Data Service',
  'Historic Environment Scotland ',
  'Austrian Academy of Sciences (OeAW)',
];
