<?php

namespace Elastic;

class QueryPeriod {

  public function __construct() {

  }

  /**
   * Get countries aggregation query from periods index
   *
   * @return array Query
   */
  public function getPeriodRegionsQuery() {
    $query['size'] = 0;
    $query['aggs'] = [
      'periodCountry' => [
        'terms' => [
          'field' => 'spatialCoverage.label.raw',
          'size' => 20,
          'order' => [ '_key' => 'asc' ]
        ]
      ]
    ];
    return $query;
  }

  /**
   * Get periods for country
   *
   * @param string Country
   * @return array Query
   */
  public function getPeriodsForCountryQuery($temporalRegion) {
    $query['size'] = 20;
    $query['sort'] = [
      'start.year' => [ 'order' => 'asc']
    ];
    $query['_source'] = [
      "authority",
      "label",
      "spatialCoverage",
      "localizedLabels",
      "start",
      "stop",
      // "spatialCoverageDescription",
    ];
    if ($temporalRegion) {
      $parts = [];
      foreach (explode('|', $temporalRegion) as $region) {
        $parts[] = [
          'match' => [
            'spatialCoverage.label.raw' => Utils::escapeLuceneValue($region),
          ],
        ];
      }
      $query['query'] = [
        'bool' => [
          'must' => [
            'bool' => [
              'should' => $parts,
            ],
          ],
        ],
      ];

    } else {
      // get all
      $query['query']['bool']['must'] = array('match_all'=> new \stdClass());
    }

    /*$query['aggregations'] = [
      'localizedLabels' => [
        'nested' => [
          'path' => 'localizedLabels'
        ],
        'aggs' => [
          'filtered_aggs' => [
            'filter' => [
              'term' => [
                'localizedLabels.language.raw' => 'en'
              ]
              ],
              'aggs' => [
                'labels' => [
                  'terms' => [
                    'field' => 'localizedLabels.label.raw',
                    'size' => 20
                  ]
                ]
              ]
          ]
        ]
      ]
    ];*/
    
    return $query;
  }

}
