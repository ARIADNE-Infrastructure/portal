<?php

namespace Elastic;

class AutocompleteFilterQuery {

  /**
   * Autocomplete query for Contributor field
   */
  public static function contributor($q,$currentQuery) {

    $query = [
      
      'size' => 0,
      'query' => $currentQuery,
      'aggregations' => [
        'filtered_agg' => [ 
          'terms' => [
            'field' => 'contributor.name.raw',
            'include' => self::getIncludeRegexp($q),
            'size' => '20',
            'order' => ['_count' => 'desc'],
          ]
        ],
        'unique_agg_count' => [
          'cardinality' => [
            'field' => 'contributor.name.raw'
          ]
        ]         
      ],
    ];
    
    return $query;

  }

  /**
   * Autocomplete query for NativeSubject field
   */
  public static function nativeSubject($q,$currentQuery) {

    $query = [
      'size' => 0,
      'query' => $currentQuery,
      'aggregations' => [
        'filtered_agg' => [ 
          'terms' => [
            'field' => 'nativeSubject.prefLabel.raw',
            'include' => '(.*'.strtolower($q).'.*)',
            'size' => '20',
            'order' => ['_count' => 'desc'],
          ] 
        ],
        'unique_agg_count' => [
          'cardinality' => [
            'field' => 'nativeSubject.prefLabel.raw'
          ]
        ] 
      ],
    ];

    return $query;

  }

  /**
   * Autocomplete query for archaeologicalResourceType field
   */
  public static function ariadneSubject($q,$currentQuery) {

    $query = [
      'size' => 0,
      'query' => $currentQuery,
      'aggregations' => [
        'filtered_agg' => [ 
          'terms' => [
            'field' => 'ariadneSubject.prefLabel.raw',
            'include' => self::getIncludeRegexp($q),
            'size' => '20',
            'order' => ['_count' => 'desc'],
          ] 
        ],
        'unique_agg_count' => [
          'cardinality' => [
            'field' => 'ariadneSubject.prefLabel.raw'
          ]
        ] 
      ],
    ];

    return $query;

  }

  /**
   * Autocomplete query for DerivedSubject field
   */
  public static function derivedSubject($q,$currentQuery) {

    $query = [
      'size' => 0,
      'query' => $currentQuery,
      'aggregations' => [
        'filtered_agg' => [ 
          'terms' => [
            'field' => 'derivedSubject.prefLabel.raw',
            'include' => '(.*'.strtolower($q).'.*)',
            'size' => '20',
            'order' => ['_count' => 'desc'],
          ] 
        ],
        'unique_agg_count' => [
          'cardinality' => [
            'field' => 'derivedSubject.prefLabel.raw'
          ]
        ]          
      ],
    ];

    return $query;

  }

  /**
   * Autocomplete query for Temporal field
   */
  public static function temporal($q,$currentQuery) {

    $query = [
      'size' => 0,
      'query' => $currentQuery,
      'aggregations' => [
        'temporal_agg' => [ 
          'nested' => [ 'path' => 'temporal'],
          'aggs' => [
            'filtered_agg' => [
              'terms' => [
                'field' => 'temporal.periodName.raw',
                'include' => '(.*'.strtolower($q).'.*)',
                'size' => '20',
                'order' => ['_count' => 'desc'],
              ] 
            ],
            'unique_agg_count' => [
              'cardinality' => [
                'field' => 'temporal.periodName.raw'
              ]
            ]
          ]
        ]
      ],
    ];

    return $query;

  }

  /**
   * Autocomplete query for Publisher field
   */
  public static function publisher($q,$currentQuery) {

    $query = [
      'size' => 0,
      'query' => $currentQuery,
      'aggregations' => [
        'filtered_agg' => [ 
          'terms' => [
            'field' => 'publisher.name.raw',
            'include' => self::getIncludeRegexp($q),
            'size' => '20',
            'order' => ['_count' => 'desc'],
          ] 
        ],
        'unique_agg_count' => [
          'cardinality' => [
            'field' => 'publisher.name.raw'
          ]
        ]
      ],
    ];

    return $query;

  }

  /**
   * Autocomplete query for Periods Countries in perio.do index
   */
  public static function temporalRegion($q,$currentQuery) {

    $query = [
      'size' => 0,
      'aggregations' => [
        'filtered_agg' => [ 
          'terms' => [
            'field' => 'spatialCoverage.label.raw',
            'include' => '('.strtolower($q).'.*)',
            'size' => '20',
            'order' => ['_count' => 'desc'],
          ] 
        ] 
      ],
    ];

    return $query;

  }

  /**
   * Autocomplete query for Periods Countries in perio.do index
   */
  public static function periods($q, $temporalRegion) {

    foreach (explode('|', $temporalRegion) as $region) {  
      if(empty($region)) {
        $filterRegionQuery['bool']['should'] = array('match_all'=> new \stdClass());
        break;
      }
      $filterRegionQuery['bool']['should'][] = [
        'term' => [
          'spatialCoverage.label.raw' => Utils::escapeLuceneValue($region),
        ],
      ];
    }

    $query['size'] = 20;

    $query['query']['bool']['must'] = [
      'nested' => [
        'path' => 'localizedLabels',
        'query' => [
          'bool' => [
            'must' => [
              [
                'wildcard' => [
                  'localizedLabels.label.raw' => $q.'*'
                ]
              ],
              [
                'match' => [
                  'localizedLabels.language' => 'en'
                ]
              ]
            ]
          ]
        ]
      ]
    ];

    $query['query']['bool']['filter'] = $filterRegionQuery;
    return $query;

  }

  /**
   * Build regular expression for include clause in Elastic query
   *  
   */
  private static function getIncludeRegexp($q) {

    $q = preg_split('/[\s]+/', $q);
    $regexpInclude = '';

    foreach( $q as $key=>$value ) {
      $regexpInclude .= '(.*'.strtolower($value).'.*|.*'.ucfirst($value).'.*|.*'.strtoupper($value).'.*)';
    }

    return $regexpInclude;

  }

}
