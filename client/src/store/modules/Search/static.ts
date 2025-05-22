import { helpText } from '../Search';

export const sortOptions = [
  { val: '_score-desc', text: 'Relevance', group: 'default' },
  { val: 'issued-desc', text: 'Issued Date (most recent)', group: 'issued' },
  { val: 'issued-asc',  text: 'Issued Date (least recent)', group: 'issued' },
  { val: 'datingto-desc',  text: 'Dating (most recent)', group: 'dating' },
  { val: 'datingfrom-asc',  text: 'Dating (least recent)', group: 'dating' },
  { val: 'publisher-asc',  text: 'Publisher (A-Z)', group: 'publisher' },
  { val: 'publisher-desc',  text: 'Publisher (Z-A)', group: 'publisher' },
  { val: 'resource-asc',  text: 'Resource type (A-Z)', group: 'resource' },
  { val: 'resource-desc',  text: 'Resource type (Z-A)', group: 'resource' },
];

export const operatorOptions = [
  { val: '', text: 'And', group: 'default' },
  { val: 'or', text: 'Or', group: 'default' },
];

export const perPageOptions = Array.from(new Array(50 / 5)).map((val, i) => {
  i = i * 5 + 5;
  return { val: i.toString(), 'text': i.toString(), group: 'default' };
});

export const helpTexts: helpText[] = [
  {
    id: '',
    title: 'All fields',
    text: '<p>Search in all fields.</p>',
  },
  {
    id: 'time',
    title: 'Time Period',
    text: '<p>Search in the Dating field (period name or date).</p>',
  },
  {
    id: 'location',
    title: 'Place',
    text: '<p>Search in Place field.</p>',
  },
  {
    id: 'title',
    title: 'Title',
    text: '<p>Search in Title field.</p>',
  },
  {
    id: 'aatSubjects',
    title: 'Getty AAT Subject',
    text: '<p>Search using a subject term from the Getty thesaurus:<br><a href="https://www.getty.edu/research/tools/vocabularies/aat/about.html" target="_blank" class="underline">https://www.getty.edu/research/tools/vocabularies/aat/about.html</a>.</p>',
  },
];

export const orderHelpTexts: helpText[] = [
  {
    id: '',
    title: 'Relevance',
    text: '<p>Calculated from several fields, Title being the most important, then Description and Subject.</p>',
  },
  {
    id: '',
    title: 'Issued date',
    text: '<p>Date of publication of the resource.</p>',
  },
  {
    id: '',
    title: 'Dating',
    text: '<p>Most recent or least recent date of the one or more dates found in each record.</p>',
  },
  {
    id: '',
    title: 'Publisher',
    text: '<p>Data provider (of the resource metadata).</p>',
  },
  {
    id: '',
    title: 'Resource type',
    text: '<p>Broad resource type.</p>',
  },
];
