export const titles = {
  archaeologicalResourceType: 'Resource type',
  spatial: 'Place',
  nativeSubject: 'Original subject',
  derivedSubject: 'Getty AAT Subjects',
  temporal: 'Dating',
  aatSubjects: 'Getty AAT Subjects',
};

// temporary separate titles for results
export const resultTitles = {
  archaeologicalResourceType: 'Resource type',
  spatial: 'Place',
  nativeSubject: 'Original subject',
  derivedSubject: 'Getty AAT Subjects',
  temporal: 'Dating',
  aatSubjects: 'Getty AAT Subjects'
};

export const types = [
  {
    id: 'archaeologicalResourceType',
    prop: 'name',
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
    param: 'subjectLabel',
    prop: 'label'
  }
];

