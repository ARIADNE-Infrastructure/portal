<?php

namespace Elastic;

class AutocompleteFilterQuery {

  /**
   * Autocomplete query for Contributor field
   */
  public static function contributor($q, $currentQuery, $size) {
    $query = [
      'size' => 0,
      'query' => $currentQuery,
      'aggregations' => [
        'filtered_agg' => [
          'terms' => [
            'field' => 'contributor.name.raw',
            'size' => $size,
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
    if ($q) {
      $query['aggregations']['filtered_agg']['terms']['include'] = self::getIncludeRegexp($q);
    }
    return $query;
  }

  /**
   * Autocomplete query for country field
   */
  public static function country($q, $currentQuery, $size) {
    $query = [
      'size' => 0,
      'query' => $currentQuery,
      'aggregations' => [
        'filtered_agg' => [
          'terms' => [
            'field' => 'country.name.raw',
            'size' => $size,
            'order' => ['_count' => 'desc'],
          ]
        ],
        'unique_agg_count' => [
          'cardinality' => [
            'field' => 'country.name.raw'
          ]
        ]
      ],
    ];
    if ($q) {
      $query['aggregations']['filtered_agg']['terms']['include'] = self::getIncludeRegexp($q);
    }
    return $query;
  }

  /**
   * Autocomplete query for data type field
   */
  public static function dataType($q, $currentQuery, $size) {
    $query = [
      'size' => 0,
      'query' => $currentQuery,
      'aggregations' => [
        'filtered_agg' => [
          'terms' => [
            'field' => 'dataType.label.raw',
            'size' => $size,
            'order' => ['_count' => 'desc'],
          ]
        ],
        'unique_agg_count' => [
          'cardinality' => [
            'field' => 'dataType.label.raw'
          ]
        ]
      ]
    ];
    if ($q) {
      $query['aggregations']['filtered_agg']['terms']['include'] = self::getIncludeRegexp($q);
    }
    return $query;
  }

  /**
   * Autocomplete query for NativeSubject field
   */
  public static function nativeSubject($q, $currentQuery, $size) {
    $query = [
      'size' => 0,
      'query' => $currentQuery,
      'aggregations' => [
        'filtered_agg' => [
          'terms' => [
            'field' => 'nativeSubject.prefLabel.raw',
            'size' => $size,
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
    if ($q) {
      $query['aggregations']['filtered_agg']['terms']['include'] = '(.*'.strtolower($q).'.*)';
    }
    return $query;
  }

  /**
   * Autocomplete query for archaeologicalResourceType field
   */
  public static function ariadneSubject($q, $currentQuery, $size) {
    $query = [
      'size' => 0,
      'query' => $currentQuery,
      'aggregations' => [
        'filtered_agg' => [
          'terms' => [
            'field' => 'ariadneSubject.prefLabel.raw',
            'size' => $size,
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
    if ($q) {
      $query['aggregations']['filtered_agg']['terms']['include'] = self::getIncludeRegexp($q);
    }
    return $query;
  }

  /**
   * Autocomplete query for DerivedSubject field
   */
  public static function derivedSubject($q, $currentQuery, $size) {
    $query = [
      'size' => 0,
      'query' => $currentQuery,
      'aggregations' => [
        'filtered_agg' => [
          'terms' => [
            'field' => 'derivedSubject.prefLabel.raw',
            'size' => $size,
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
    if ($q) {
      $query['aggregations']['filtered_agg']['terms']['include'] = '(.*'.strtolower($q).'.*)';
    }
    return $query;
  }

  /**
   * Autocomplete query for Temporal field
   */
  public static function temporal($q, $currentQuery, $size) {
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
                'size' => $size,
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
    if ($q) {
      $query['aggregations']['temporal_agg']['aggs']['filtered_agg']['terms']['include'] = '(.*'.strtolower($q).'.*)';
    }
    return $query;
  }

  /**
   * Autocomplete query for Publisher field
   */
  public static function publisher($q, $currentQuery, $size) {
    $query = [
      'size' => 0,
      'query' => $currentQuery,
      'aggregations' => [
        'filtered_agg' => [
          'terms' => [
            'field' => 'publisher.name.raw',
            'size' => $size,
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
    if ($q) {
      $query['aggregations']['filtered_agg']['terms']['include'] = self::getIncludeRegexp($q);
    }
    return $query;
  }

  /**
   * Autocomplete query for Periods Countries in perio.do index
   */
  public static function temporalRegion($q, $currentQuery, $size) {
    $query = [
      'size' => 0,
      'aggregations' => [
        'filtered_agg' => [
          'terms' => [
            'field' => 'spatialCoverage.label.raw',
            'size' => $size,
            'order' => ['_count' => 'desc'],
          ]
        ]
      ],
    ];
    if ($q) {
      $query['aggregations']['filtered_agg']['terms']['include'] = '('.strtolower($q).'.*)';
    }
    return $query;
  }

  /**
   * Autocomplete query for Periods Countries in perio.do index
   */
  public static function periods($q, $temporalRegion, $size) {
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

    $query['size'] = $size;
    $query['sort'] = ['start.year' => ['order' => 'asc']];

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
