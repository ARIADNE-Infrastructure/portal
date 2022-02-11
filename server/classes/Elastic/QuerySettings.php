<?php

namespace Elastic;

class QuerySettings {

  private const AGGS_BUCKET_SIZE = 20;

  public static function getSearchSort() {
    return [
      'issued',
      'title',
      '_score'
    ];
  }

  /**
   * Search only in these fields for user inserted query string when selecting "Search all".
   */
  public static function getValidSearchableFields($searchString = '') {

    return [
      [ 'match' => [ 'title.text' => $searchString ] ],
      [ 'match' => [ 'description.text' => $searchString ] ],
      [
        [
          'nested' => [
            'path' => 'spatial',
            'query' => [ 'match' => [ 'spatial.placeName' => $searchString ] ]
          ]
        ]           
      ],
      [
        [
          'nested' => [
            'path' => 'temporal',
            'query' => [ 'match' => [ 'temporal.periodName' => $searchString ] ]
          ]
        ]           
      ],
      [ 'match' => [ 'nativeSubject.prefLabel' => $searchString ] ],
      [ 'match' => [ 'derivedSubject.prefLabel' => $searchString ] ]                      
    ];

  }  

  /**
   * Mapp URI 'fields' and ES mapping names
   */
  public static function getSearchFieldGroups() {
    return [
      'time'          => ['temporal.periodName'],
      'location'      => ['spatial.placeName'],
      'title'         => ['title.text'],
      'nativeSubject' => ['nativeSubject.prefLabel'],
      'aatSubjects'   => ['derivedSubject.prefLabel']
    ];
  }

  public static function getSearchAggregations() {
    
    return [
      'ariadneSubject' => [
        'terms' => [
          'field' => 'ariadneSubject.prefLabel.raw',
          'size' => self::AGGS_BUCKET_SIZE
        ]
      ],
      'derivedSubject' => [
        'terms' => [
          'field' => 'derivedSubject.prefLabel.raw',
          'size' => self::AGGS_BUCKET_SIZE
        ]
      ],
      'contributor' => [
        'terms' => [
          'field' => 'contributor.name.raw',
          'size' => self::AGGS_BUCKET_SIZE
        ]
      ],
      'publisher' => [
        'terms' => [
          'field' => 'publisher.name.raw',
          'size' => self::AGGS_BUCKET_SIZE
        ]
      ],
      'temporal' => [
        'nested' => [
          'path' => 'temporal'
        ],
        'aggs' => [
          'temporal' =>[
            'terms' => [
              'field' => 'temporal.periodName.raw',
              'size' => self::AGGS_BUCKET_SIZE
            ],
            'aggs' =>[
              'top_reverse_nested'=>[
                'reverse_nested'=> new \stdClass()
              ]
            ]
          ]
        ]
      ],
      'nativeSubject' => [
        'terms' => [
          'field' => 'nativeSubject.prefLabel.raw',
          'size' => self::AGGS_BUCKET_SIZE
        ]
      ]
    ];
  }
}
