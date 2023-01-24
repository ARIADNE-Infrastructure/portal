<?php

namespace Elastic;

use Application\AppSettings;
use Elastic\Timeline;
use AAT\AATDescendants;

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
        'query' => [
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
        'query' => [
          'match_phrase' => [
            'originalId' => [
              'query' => $searchString,
              'operator' => 'and'
            ]
          ]
        ]
      ],
      'otherId' => [
        'fieldPath' => 'otherId',
        'query' => [
          'match_phrase' => [
            'otherId' => [
              'query' => $searchString,
              'operator' => 'and'
            ]
          ]
        ]
      ],
    ];
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
    foreach ($inParams as $param => $paramValue) {

      /*
        Den här funktionen har passerat sin 'bra-tid' efter alla förändringar och önskade
        kombinationer av querys och resultat. Därför bör man göra om detta för att göra saken enklare
        att felsöka, förändra, läsa och tolka kod. Det kommer bli fler rader kod och kanske en del
        upprepningar, men det är det värt!

        Vad man bör göra är följande:
        För vare inkommande parameter (som är någon form av filter), gör ett anrop till separat funktion
        som returnerar en komplett formaterad query istället för att 'dynamiskt' bygga dessa med hjälp av
        'inställningar' i funktionen getValidFilters. Den här designen håller inte längre och för varje gång
        man behöver göra en förändring/förbättring på grund av önskad och beställd funktion så blir koden mer
        och mer komplex och svår att tyda. Det är mycket bättre att ha fler rader lättläst och tydlig kod!
        Ett exemepl på komplecxiteten är när vi nu vill göra en OR sökning på filtret 'culturalPeriods' som
        används i POC:en för Period filter (på Staging).
      */

      // filters may be multiple separated by pipe character (|)
      $paramsArr = explode('|', $paramValue);

      // cultural periods - POC - Period search
      if ($param === 'culturalPeriods') {
        $filters[] = [
          'bool' => [
            'should' => [
              'nested' => [
                'path' => 'temporal',
                'query' => [
                  'bool' => [
                    'should' => [
                      'terms' => [
                        'temporal.uri' => $paramsArr,
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ];

      // others
      } else {
        foreach ($paramsArr as $value) {
          $filter = self::getValidFilter($param, $value);
          if ($filter) {
            $filterVal = $filter['innerQuery'] ?? [];
            if ($filter['isNested']) {
              $filterVal = [
                'nested' => [
                  'path' => $filter['fieldPath'],
                  'query' => [
                    'bool'=> [
                      'must' => !empty($filter['isArrayNested']) ? $filter['innerQuery'] : [ $filter['innerQuery'] ],
                    ],
                  ],
                ],
              ];
            }
            if (isset($filter['operator']) && $filter['operator'] === 'OR') {
              $filters[]['bool']['should'] = $filterVal;
            } else {
              $filters[] = $filterVal;
            }
          }
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
      'bbox' => [
        'fieldPath' => 'temporal',
        'isNested' => false,
        'operator' => 'OR',
        'innerQuery' => self::getBoundingboxFilter()
      ],
      'ariadneSubject' => [
        'fieldPath' => 'ariadneSubject',
        'isNested' => false,
        'innerQuery' => ['term' => ['ariadneSubject.prefLabel.raw' => $filterValue]]
      ],
      'derivedSubject' => [
        'fieldPath' => 'derivedSubject',
        'isNested' => false,
        'innerQuery' => self::getDerivedSubjectDescendats($filterName, $filterValue)
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
      'isPartOf' => [
        'fieldPath' => 'isPartOf',
        'isNested' => false,
        'innerQuery' => ['match_phrase' => ['isPartOf' => $filterValue]]
      ],
      'range' => [
        'fieldPath' => 'temporal',
        'isNested' => false, // outer OR filter is not nested,
        'operator' => 'OR',
        'isArrayNested' => true,
        'innerQuery' => $filterName !== 'range' ?: Timeline::buildRangeIntersectingInnerQuery($filterValue)
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

  /**
   * Get filters for query for bbox param in $_GET
   *
   */
  public static function getBoundingboxFilter() {
    if( isset($_GET['bbox']) ) {
      $bbox = explode(',', $_GET['bbox']);
      // Geopoints
      $boundingBoxFilters[] = [
        'nested' => [
          'path' => 'spatial',
          'query' => [
            'geo_bounding_box' => [
                'spatial.geopoint' => [
                  'top_left' => [
                    'lat' => floatval($bbox[0]),
                    'lon' => floatval($bbox[1])
                  ],
                  'bottom_right' => [
                    'lat' => floatval($bbox[2]),
                    'lon' => floatval($bbox[3])
                  ]
                ]
            ]
          ]
        ]
      ];

      // Possible Geo shapes nested query
      // topLeft.lon, topLeft.lat, bottomRight.lon,bottomRight.lat
      $possibleGeoShapes = ['polygon','boundingbox'];
      foreach($possibleGeoShapes as $geoShape) {
        $boundingBoxFilters[] = [
          'nested' => [
            'path' => 'spatial',
            'query' => [
              'geo_shape' => [
                'spatial.'.$geoShape => [
                  'shape' => [
                    'type' => 'envelope',
                    'relation' => 'within',
                    'coordinates' => [
                      [
                        floatval($bbox[1]),
                        floatval($bbox[0])
                      ],
                      [
                        floatval($bbox[3]),
                        floatval($bbox[2])
                      ]
                    ]
                  ]
                ]
              ]
            ]
          ]
        ];
      }
      return $boundingBoxFilters;
    }
    return [];

  }


  /**
   * Get AAT term descendants for given id from OpenSearch index
   */
  private static function getDerivedSubjectDescendats($filterName, $term) {

    if($filterName === 'derivedSubject') {

      // get descendants from AAT-descendats index.
      $aat = new AATDescendants();
      $result = $aat->getDescendants($term);
      $descendants = [];

      if(!empty($result)) {
        foreach($result as $val) {
          // TODO: when ariadne index has derivedSubject.id as keyword type, remove
          // substr(strrchr($val['uri'], "/"), 1) below, just search for the whole key
          $descendants[] = substr(strrchr($val['uri'], "/"), 1);
        }
      } else {
        /* TODO: NOTICE FALLBACK NOTICE:
           This is used as FALLBACK meanwhile we wait for AAT index and AAT-descendants-index to be updated.
           Current situation is that all requested AATs are supposed to return self plus all descendant
           terms (children), e.g. weapons should return weapaons + knives + pistols + daggers etc.

           At the moment, there are lots of AAT terms in Ariadne resources (Ariadne-index) that are missing in AAT-index and therefore also in AAT-descendants-index.
           Therefore, when requested AAT-term is not found in AAT-descendants we return $term, because we know that incoming $term, in this case,
           is derived from an Ariadne-index resource.
           Be aware about the fact that this only results in resources with exact $term match, and nothing else!!

           This fallback can be triggered, as an example, by navigating to /search?q=&derivedSubject=settlements (sites of small communities).
           The result of this is that (without this fallback) the term "/settlements (sites of small communities)" with URI/ID: "http://vocab.getty.edu/aat/300444153"
           DOES exist in Ariadne-index resource(s) but the URI/ID "300444153" DOES NOT exist in AAT-descendants-index. Resulting in ZERO results.

           Okay, so in this case, we simply look for exact term match in Ariadne-index, discriminating all descendant terms!
           When/if AAT-index and AAT-descendants-index are updated you can choose to remove this fallback or keep it as is
           but I believe this would be redundant, since all AAT-terms residing in Ariadne-index also should exist in AAT-descendant-index and v.v. */
          return [
            'term' => [
              //'derivedSubject.prefLabel.raw' => $descendants
              'derivedSubject.prefLabel.raw' => $term
            ]
          ];
      }

      return [
        'terms' => [
          //'derivedSubject.prefLabel.raw' => $descendants
          'derivedSubject.id' => $descendants
        ]
      ];

    }
  }

}
