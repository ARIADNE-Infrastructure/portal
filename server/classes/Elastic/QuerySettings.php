<?php

namespace Elastic;

use Elastic\Timeline;

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
   * Multimatch query with valid searchable fields
   */
  public static function getMultiMatchQuery($searchString = '') {
    $fieldPath = array_column(self::getValidSearchableFields(), 'fieldPath');
    $matchQuery = [
      'multi_match' => [
        'query' => $searchString,
        'fields' =>  array_values($fieldPath),
        'operator' => 'AND'
      ]
    ];
    return $matchQuery;
  }

  /**
   * Valid searchable fields with metadata for constructing Elastic querys.
   * NOTICE: These fields are the only fields allowed to be searchable by user
   * input in combo with or without q-param.
   *
   * @param $searchString
   * @return array
   */
  public static function getValidSearchableFields($searchString = ''): array {

    return [
      'title' => [
        'fieldPath' => 'title.text^4',
        'query' =>
          [
            'match' => [
              'title.text' => [
                'query' => $searchString,
                'operator' => 'and'
              ]
            ]
          ]
      ],
      'description' => [
        'fieldPath' => 'description.text^3',
        'query' => [
          'match' => [
            'description.text' => [
              'query' => $searchString,
              'operator' => 'and'
            ]
          ]
        ]
      ],
      'nativeSubject' => [
        'fieldPath' => 'nativeSubject.prefLabel^2',
        'query' => [
          'match' => [
            'nativeSubject.prefLabel' => [
              'query' => $searchString,
              'operator' => 'and'
            ]
          ]
        ]
      ],
      'derivedSubject' => [
        'fieldPath' => 'derivedSubject.prefLabel^2',
        'query' => [
          'match' => [
            'derivedSubject.prefLabel' => [
              'query' => $searchString,
              'operator' => 'and'
            ]
          ]
        ]
      ],
      'location' => [
        'fieldPath' => 'spatial.placeName',
        'query' => [
          [
            'nested' => [
              'path' => 'spatial',
              'query' => [
                'match' => [
                  'spatial.placeName' => [
                    'query' => $searchString,
                    'operator' => 'and'
                  ]
                ]
              ]
            ]
          ]
        ]
      ],
      'time' => [
        'fieldPath' => 'temporal.periodName',
        'query' => [
          [
            'nested' => [
              'path' => 'temporal',
              'query' => [
                'match' => [
                  'temporal.periodName' => [
                    'query' => $searchString,
                    'operator' => 'and'
                  ]
                ]
              ]
            ]
          ]
        ]
      ],
      'originalId' => [
        'fieldPath' => 'originalId',
        'query' =>
          [
            'match_phrase' => [
              'originalId' => [
                'query' => $searchString,
                'operator' => 'and'
              ]
            ]
          ]
      ],                  
    ];

  }

  /**
   * Helper function for filter querys
   */
  private static function getFilterInnerQuery($filter): array {
    if($filter['isNested']) {
      return [
        'nested' => [
          'path' => $filter['fieldPath'],
          'query' => [
            'bool'=> [
              'must' => !empty($filter['isArrayNested']) ? $filter['innerQuery'] : [ $filter['innerQuery'] ],
            ]
          ]
        ]
      ];
    } else {
      return $filter['innerQuery'];
    }
  }


  /**
   * Match incoming get params to valid filters.
   * If found, filters are pushed to filter array
   *
   * @param $inParam equivalent to $_GET content
   * @return $filters Array with valid filters to throw at Elastic
   */
  public static function getFilters($inParams) {
    $filters = [];
    // loop all inParams, check if it's a valid filter
    foreach($inParams as $param => $paramValue) {
      foreach(explode('|', $paramValue) as $value) {
        // filters may be multiple separated by pipe character (|)
        $filter = self::getValidFilter($param, $value);
        if($filter) {
          $filters[] = self::getFilterInnerQuery($filter);
        }
      }
    }
    return $filters;
  }


  /**
   * Get filter query metadata
   */
  public static function getValidFilter($filterName, $filterValue) {

    $validFilters =  [
      'ariadneSubject' => [
        'fieldPath' => 'ariadneSubject',
        'isNested' => false,
        'innerQuery' => ['term' => ['ariadneSubject.prefLabel.raw' => $filterValue]]
      ],
      'derivedSubject' => [
        'fieldPath' => 'derivedSubject',
        'isNested' => false,
        'innerQuery' => ['term' => ['derivedSubject.prefLabel.raw' => $filterValue]]
      ],
      'contributor' => [
        'fieldPath' => 'contributor',
        'isNested' => false,
        'innerQuery' => ['term' => ['contributor.name.raw' => $filterValue]]
      ],
      'publisher' => [
        'fieldPath' => 'publisher',
        'isNested' => false,
        'innerQuery' => ['term' => ['publisher.name.raw' => $filterValue]]
      ],
      'temporal' => [
        'fieldPath' => 'temporal',
        'isNested' => true,
        'innerQuery' => ['term' => ['temporal.periodName.raw' => $filterValue]] // NOTE: Should this be match_phrase instead?
      ],
      'nativeSubject' => [
        'fieldPath' => 'nativeSubject',
        'isNested' => false,
        'innerQuery' => ['term' => ['nativeSubject.prefLabel.raw' => $filterValue]]
      ],
      'geogrid' => [
        'fieldPath' => 'spatial',
        'isNested' => true,
        'innerQuery' => ['term' => ['spatial.geopoint' => $filterValue]]
      ],
      'creator' => [
        'fieldPath' => 'creator',
        'isNested' => false,
        'innerQuery' => ['term' => ['creator.name.raw' => $filterValue]]
      ],
      'owner' => [
        'fieldPath' => 'owner',
        'isNested' => false,
        'innerQuery' => ['term' => ['owner.name.raw' => $filterValue]]
      ],
      'responsible' => [
        'fieldPath' => 'responsible',
        'isNested' => false,
        'innerQuery' => ['term' => ['responsible.name.raw' => $filterValue]]
      ],
      'resourceType' => [
        'fieldPath' => 'resourceType',
        'isNested' => false,
        'innerQuery' => ['term' => ['resourceType' => $filterValue]]
      ],
      'placeName' => [
        'fieldPath' => 'spatial', // NOTE: Should this field do a match_phrase instead?
        'isNested' => true,
        'innerQuery' => ['term' => ['spatial.placeName.raw' => $filterValue]]
      ],
      // SPECIALS
      'derivedSubjectId' => [
        'fieldPath' => 'derivedSubject',
        'isNested' => false,
        'innerQuery' => ['term' => ['derivedSubject.id' => $filterValue]]
      ],
      'isPartOf' => [
        'fieldPath' => 'isPartOf',
        'isNested' => false,
        'innerQuery' => ['match_phrase' => ['isPartOf' => $filterValue]]
      ],
      'range' => [
        'fieldPath' => 'temporal',
        'isNested' => true,
        'isArrayNested' => true,
        'innerQuery' => $filterName !== 'range' ?: Timeline::buildRangeInnerQuery($filterValue)
      ],
      // POC - Period search
      'period' => [
        'fieldPath' => 'temporal',
        'isNested' => true,
        'innerQuery' => ['term' => ['temporal.periodName.raw' => strtolower($filterValue)]]
      ],      
    ];

    //return $validFilters;
    if(array_key_exists($filterName, $validFilters)) {
      return $validFilters[$filterName];
    }
    return;

  }


  /**
   * Aggregations
   */
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
      ],
      // remove when centroids are uploaded to public
      'geogrid' => [
        'nested' => [
          'path' => 'spatial'
        ],
        'aggs' => [
          'grids' => [
            'geohash_grid' => [
              'field' => 'spatial.geopoint',
              'precision' => 7,
              'size' => 5000
            ]
          ]
        ]
      ],
      'geogrid_centroid' => [
        'nested' => [
          'path' => 'spatial'
        ],
        'aggs' => [
          'grids' => [
            'geohash_grid' => [
              'field' => 'spatial.centroid',
              'precision' => 7,
              'size' => 5000
            ]
          ]
        ]
      ]            
    ];
  }

}
