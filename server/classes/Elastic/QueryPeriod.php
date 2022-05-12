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
  public function getCountriesAggQuery() {
    $query['size'] = 0;
    $query['aggregations'] = [
      'periodCountries' => [
        'terms' => [
          'field' => 'spatialCoverage.label.raw',
          'size' => 250
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
  public function getPeriodsForCountryQuery($countryLabel) {
    $query['size'] = 50;
    $query['_source'] = [
      "spatialCoverage",
      "localizedLabels",
      "spatialCoverageDescription",
      "start",
      "stop"
    ];
    if($countryLabel !== '') {
      $query['query'] = [
        'bool' => [
          'must' => [
            'term' => [
              'spatialCoverage.label.raw' => $countryLabel
            ]
          ]
        ]
      ];        
    } else {
      // get all
      $query['query']['bool']['must'] = array('match_all'=> new \stdClass());
    }

    return $query;
  }  

}
