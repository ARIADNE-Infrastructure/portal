import { helpText } from '../Search';

export const sortOptions = [
  { val: '_score-desc', text: 'Relevance', group: 'default' },
  { val: '_score-asc', text: 'Relevance', group: 'default' },
  { val: 'issued-desc', text: 'Issued Date', group: 'default' },
  { val: 'issued-asc',  text: 'Issued Date', group: 'default' },
  // { val: 'title-desc', text: 'Title - DESC' },
  // { val: 'title-asc',  text: 'Title - ASC' },
];

export const helpTexts: helpText[] = [
  {
    id: '',
    title: 'All fields',
    text: '<p>Search in all fields</p>',
  },
  {
    id: 'time',
    title: 'Time Period',
    text: '<p>Search in Time Period field</p>',
  },
  {
    id: 'location',
    title: 'Place',
    text: '<p>Search in Place field</p>',
  },
  {
    id: 'title',
    title: 'Title',
    text: '<p>Search in Title field</p>',
  },
  {
    id: 'aatSubjects',
    title: 'Getty AAT Subject',
    text: '<p>Search using a subject term from the Getty thesaurus:<br><a href="https://www.getty.edu/research/tools/vocabularies/aat/about.html" target="_blank" class="underline">https://www.getty.edu/research/tools/vocabularies/aat/about.html</a></p>',
  },
];
