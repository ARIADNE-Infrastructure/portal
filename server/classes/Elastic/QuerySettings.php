<?php

namespace Elastic;

class QuerySettings {


  public static function getSearchSort() {
    return $elastic_search_sort = [
      'issued',
      'title',
      '_score'
    ];
  }

  public static function getSearchFieldGroups() {
    return [
      'time' => ['temporal.periodName'],
      'location' => ['spatial.placeName'],
      'identifier' => ['identifier', 'originalId'],
      'title' => ['title'],
      'nativeSubject' => ['nativeSubject.prefLabel'],
      //'subject' => ['nativeSubject.prefLabel', 'aatSubjects.label', 'derivedSubject.prefLabel'],
      //'derivedSubject' => ['derivedSubject.prefLabel'],
      //'subjectUri' => ['derivedSubject.source']
    ];
  }

  public static function getSearchAggregations() {

    return $elastic_search_aggregations = [
    'archaeologicalResourceType' => [
      'terms' => [
        'field' => 'archaeologicalResourceType.name.raw'
      ]
    ],
    'derivedSubject' => [
      'terms' => [
        'field' => 'derivedSubject.prefLabel.raw'
      ]
    ],
    'keyword' => [
      'terms' => [
        'field' => 'keyword.raw'
      ]
    ],
    'contributor' => [
      'terms' => [
        'field' => 'contributor.name.raw'
      ]
    ],
    'publisher' => [
      'terms' => [
        'field' => 'publisher.name.raw'
      ]
    ],
    'temporal' => [
      'nested' => [
        'path' => 'temporal'
      ],
      'aggs' => [
        'temporal' =>[
          'terms' => [
            'field' => 'temporal.periodName.raw'
          ],
          'aggs' =>[
            'top_reverse_nested'=>[
              'reverse_nested'=> new \stdClass()
            ]
          ]
        ]
      ]
    ],
    'issued' => [
      'terms' => [
        'field' => 'issued.raw'
      ]
    ],
    'nativeSubject' => [
      'terms' => [
        'field' => 'nativeSubject.prefLabel.raw'
      ]
    ]
  ];
}




}



