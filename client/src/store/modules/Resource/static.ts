export const fields = [
  { val: '', text: 'All fields' },
  { val: 'time', text: 'Time period' },
  { val: 'location', text: 'Place' },
  { val: 'title', text: 'Title' },
  { val: 'nativeSubject', text: 'Getty AAT Subject' },
];

export const types = {
  1: 'excavation',
  2: 'event',
  3: 'site',
  4: 'scientific',
  5: 'artefact',
  6: 'burial',

  // new
  7: 'date',
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

export const typesTemporary = {
  'Artefact': 'artefact',
  'Date': 'date', 
  'Fieldwork': 'excavation',
  'Fieldworkarchive': 'excavation', // later switch to new icon (spade w/ database)
  'Fieldworkreport': 'excavation', // later switch to new icon (spade w/ text document)
  'Inscription': 'inscription', // later to new icon
  'RockArt': 'inscription', // later switch to new icon
  'Site/monument': 'site',
};

export const thematicals = {
  '': 'Subject & Time period',
  'title': 'Title',
  'location': 'Location',
  'subject': 'Subject',
  'temporal': 'Time period',
};
