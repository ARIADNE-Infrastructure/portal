<?php

namespace Elastic;

use Application\AppSettings;
use Elastic\QuerySettings;
use Elastic\Timeline;
use Elastic\Utils;
use Elastic\GeoUtils;
use Elasticsearch\ClientBuilder;
use Periodo\Periodo;

class Query {
  private static $inst = null;
  private $settings;
  private $elasticEnv;
  private $client = null;
  private $aggregationsReqFilter = []; // Aggregation filters from uri

  public function __construct () {
    $this->settings = AppSettings::getSettings();
    $this->elasticEnv = AppSettings::getSettingsEnv();
  }

  // returns instance of class (singleton)
  public static function instance() {
    if (!self::$inst) {
      self::$inst = new Query();
    }
    return self::$inst;
  }

  // get elastic client
  public function getClient () {
    if (!$this->client) {
      $this->client = ClientBuilder::create()->setHosts([$this->elasticEnv->host])->build();
    }
    return $this->client;
  }

  // returns default language from settings
  public function getDefaultLanguage () {
    return $this->settings->environment->defaultLanguage;
  }

  /**
   * Search Elastic db with given URI parameters
   */
  public function search () {
    if (isset($_GET['mapq'])) {
      return $this->getSearchAggregationData();
    }
    return $this->resultToFrontend($this->elasticDoSearch($this->getCurrentQuery()));
  }

  /**
   * Build main elastic consumable query from/with GET params
   * Notice, this is the main body query, aggregations excluded!
   */
  private function getCurrentQuery () {
    $query['size'] = $this->getSize();
    $query['from'] = $this->getFrom();
    $query['sort'] = $this->getSort();
    $operator = !empty($_GET['operator']) && $_GET['operator'] === 'or' ? 'or' : 'and';
    $filters = [];
    $innerQuery = [];

    if (isset($_GET['q'])) {
      $_GET['q'] = trim($_GET['q']);
    }

    // Handle incoming user input query string
    if (empty($_GET['q'])) {
      $innerQuery['bool']['should'] = ['match_all' => new \stdClass()];

    } else {
      $raw = Utils::escapeLuceneValue($_GET['q'], false);
      $search = sprintf('%s | "%s"', $raw, $raw);
      $searchField = !empty($_GET['fields']) ? trim($_GET['fields']) : null;
      $fields = QuerySettings::getValidSearchableFields();

      if ($searchField && !empty($fields[$searchField])) {
        $fieldQuery = [
          'simple_query_string' => [
            'default_operator' => $operator,
            'fields' => [explode('^', $fields[$searchField]['fieldPath'])[0]],
            'query' => $searchField === 'title' ? sprintf('%s | %s*', $search, $raw) : $search,
          ],
        ];
        if (!empty($fields[$searchField]['nested'])) {
          $innerQuery['bool'] = ['minimum_should_match' => 1, 'should' => ['nested' => ['path' => $fields[$searchField]['nested'], 'query' => $fieldQuery]]];
        } else {
          $innerQuery['bool'] = ['minimum_should_match' => 1, 'should' => $fieldQuery];
        }

      } else {
        $searchFields = [];
        $nestedFields = [];
        foreach ($fields as $key => $val) {
          $nested = $val['nested'] ?? null;
          if (empty($nested)) {
            $searchFields[] = $val['fieldPath'];
          } else {
            $nestedFields[] = [
              'nested' => [
                'path' => $nested,
                'query' => [
                  'simple_query_string' => [
                    'default_operator' => $operator,
                    'fields' => [$val['fieldPath']],
                    'query' => $search,
                  ],
                ],
              ],
            ];
          }
        }
        $innerQuery['bool'] = [
          'minimum_should_match' => 1,
          'should' => array_merge([
            [
              'simple_query_string' => [
                'default_operator' => $operator,
                'fields' => $searchFields,
                'query' => $search,
              ],
            ],
            [
              'simple_query_string' => [
                'default_operator' => $operator,
                'query' => $search,
              ],
            ],
            [
              'simple_query_string' => [
                'default_operator' => $operator,
                'fields' => [
                  $fields['title']['fieldPath'] . '0',
                ],
                'query' => $raw . '*',
              ],
            ],
          ], $nestedFields),
        ];
      }
    }

    // push inner query to main query
    $query['query'] = $innerQuery; // Attach inner query

    // Push filters
    $filters = QuerySettings::getFilters($_GET);

    // Push filters to main query
    if (!empty($filters)) {
      $musts = [];
      foreach ($filters as $filter) {
        switch ($_GET['operator'] ?? '') {
          case 'or': $query['query']['bool']['filter']['bool']['should'][] = $filter; break;
          case 'and': $query['query']['bool']['filter'][] = $filter; break;
          default:
            $term = $filter['term'] ?? $filter['terms'] ?? $filter['nested']['query']['bool']['must'][0]['term'] ?? null;
            if (!empty($term)) {
              $musts[array_key_first($term)][] = $filter;
            } else {
              $query['query']['bool']['filter']['bool']['must'][] = $filter;
            }
        }
      }
      foreach ($musts as $key => $val) {
        $query['query']['bool']['filter']['bool']['must'][] = ['bool' => ['should' => $val]];
      }
    }

    return $query;
  }

  /**
   * Map specific query.
   * Adds additional map parameters to main query
   */
  private function getMapQuery ($mainQuery) {
    $mainQuery['_source'] = ['title', 'description', 'resourceType', 'publisher', 'ariadneSubject', 'spatial'];

    // // Viewport
    $mainQuery['aggregations']['viewport'] = [
      'nested' => [
        'path' => 'spatial'
      ],
      'aggs' => [
        'thisBounds' => [
          'geo_bounds' => [
            'field' => 'spatial.geopoint',
            'wrap_longitude' => true,
          ]
        ]
      ]
    ];

    // Filter result. Get only resources with spatial data
    $filterType = empty($_GET['operator']) || $_GET['operator'] === 'or' ? 'must' : 'filter';
    $mainQuery['query']['bool'][$filterType][] = [
      'nested' => [
        'path' => 'spatial',
        'query' => [
          'bool' => [
            'should' => [
              ['exists' => ['field' => 'spatial.geopoint']], // remove when centroids are uploaded to public
            ]
          ]
        ]
      ]
    ];

    /* Do roundtrip to ES to see if result is more than 500. If result count is more
       than 500 the map doesn't need records data because it's rendering heatmap with
       data from aggregations->geogrid */
    $count = 0;
    try {
      $result = $this->getClient()->count([
        'index' => $this->elasticEnv->index,
        'body' => ['query' => $mainQuery['query']],
      ]);
      $count = intval($result['count'] ?? 0);
    } catch (\Exception $ex) {
      if (AppSettings::isLogging()) {
        AppSettings::debugLog($ex->getMessage());
      }
    }
    if ($count <= $this->settings->environment->mapMarkerThreshhold) { // Render markers - records needed to render markers
      $mainQuery['size'] = $this->settings->environment->mapMarkerThreshhold;
      $center = [25.3167, 54.9000]; // default value if no boudning box - center of europe
      if (isset($_GET['bbox'])) {
        $bbox = explode(',', $_GET['bbox']);
        $aLat = floatval($bbox[0] ?? 0);
        $aLon = floatval($bbox[1] ?? 0);
        $bLat = floatval($bbox[2] ?? 0);
        $bLon = floatval($bbox[3] ?? 0);
        $center = [($aLon + $bLon) / 2, ($aLat + $bLat) / 2];
      }
      $mainQuery['sort'] = [
        '_geo_distance' => [
          'spatial.geopoint' => $center,
          'order' => 'asc',
          'mode' => 'min',
          'nested' => [
            'path' => 'spatial',
          ],
        ],
      ];
    } else {
      $mainQuery['size'] = 0;
    }
    array_push($mainQuery['query']['bool']['must'][count($mainQuery['query']['bool'][$filterType]) - 1]['nested']['query']['bool']['should'],
      ['exists' => ['field' => 'spatial.polygon']], // remove when centroids are uploaded to public
      ['exists' => ['field' => 'spatial.boundingbox']], // remove when centroids are uploaded to public
      ['exists' => ['field' => 'spatial.centroid']]
    );
    return $mainQuery;
  }

  /**
   * Get data specific for aggregations / filters
   */
  public function getSearchAggregationData () {
    $query = $this->getCurrentQuery();
    $query['aggregations'] = QuerySettings::getSearchAggregations();
    $operator = $_GET['operator'] ?? '';
    $pinned = [];
    $query['size'] = 0;
    unset($query['_source']);
    unset($query['sort']);
    unset($query['from']);

    // timeline specific query
    if (!empty($_GET['timeline'])) {
      $range = empty($_GET['range']) ? null : explode(',', $_GET['range']);
      $query['aggregations'] = ['range_buckets' => Timeline::prepareRangeBucketsAggregation($range)];

    // This is the map search requesting data. Push map specific queri attributes to main query
    } elseif (isset($_GET['mapq']) && !isset($_GET['mapqAggs'])) {
      $query = $this->getMapQuery($query);

    } else {
      unset($query['aggregations']['geogridCentroid']);

      // unset filters if it's "or" search
      if (empty($operator) || $operator === 'or') {
        unset($query['query']['bool']['filter']);
      }

      // logic for "and + or" filters
      if (empty($operator)) {
        $musts = [];
        foreach (QuerySettings::getFilters($_GET) as $filter) {
          $term = $filter['term'] ?? $filter['terms'] ?? $filter['nested']['query']['bool']['must'][0]['term'] ?? null;
          if (!empty($term)) {
            $musts[array_key_first($term)][] = $filter;
          } elseif (isset($_GET['range']) && (!empty($filter['bool']['should'][0]['nested']['query']['bool']['must'][0]['range'] ?? null))) {
            $musts['range'][] = $filter;
          } elseif (isset($_GET['range']) && (!empty($filter['bool']['should'][0]['nested']['query']['geo_bounding_box'] ?? null))) {
            $musts['bbox'][] = $filter;
          } elseif (isset($_GET['culturalPeriods']) && (!empty($filter['bool']['should']['nested']['query']['bool']['should']['terms']['temporal.uri'] ?? null))) {
            $musts['periods'][] = $filter;
          }
        }
        foreach ($query['aggregations'] as $aggKey => &$agg) {
          $has = false;
          foreach ($musts as $key => $val) {
            $field = $agg['terms']['field'] ?? $agg['aggs'][$aggKey]['terms']['field'] ?? null;
            if (!empty($field) && $key !== $field) {
              $agg['filter']['bool']['must'][] = ['bool' => ['should' => $val]];
              $has = true;
            }
          }
          if ($has) {
            if (!empty($agg['nested'])) {
              $agg = ['aggregations' => [$aggKey => ['nested' => $agg['nested'], 'aggregations' => $agg['aggs']]], 'filter' => $agg['filter']];
            } else {
              $agg = ['aggregations' => [$aggKey => ['terms' => $agg['terms']]], 'filter' => $agg['filter']];
            }
          }
        }
        if (!empty($musts)) {
          foreach ($musts as $pinKey => $pinAggs) {
            $key = explode('.', $pinKey)[0];
            if (!empty($query['aggregations'][$key])) {
              $count = 0;
              $nested = $key === 'temporal';
              foreach ($pinAggs as $pinAgg) {
                $pinId = $pinKey;
                $pinVal = $nested ? '' : ($pinAgg['term'] ?? $pinAgg['terms'])[$pinKey];
                if ($nested || is_array($pinVal)) {
                  $pinId = $key . ($nested ? '.periodName.raw' : '.prefLabel.raw');
                  $pinVal = explode('|', $_GET[$key])[$count] ?? '';
                }
                $pinned[$key . '_' . $count] = $pinVal;
                $query['aggregations']['pinned']['filters']['filters'][$key . '_' . ($count++)] = ['bool' => ['must' => array_merge($query['aggregations'][$key]['filter']['bool']['must'] ?? [], [['bool' => ['should' => ($nested ? [['nested' => ['path' => $key, 'query' => ['bool' => ['must' => [['term' => [$pinId => $pinVal]]]]]]]] : ['term' => [$pinId => $pinVal]])]]])]];
              }
            }
          }
        }
      }
    }
    $result = $this->elasticDoSearch($query);
    if (!empty($pinned) && !empty($result['aggregations']['pinned']['buckets'])) {
      $pins = [];
      foreach ($pinned as $key => $val) {
        $pins[] = ['key' => $val, 'type' => explode('_', $key)[0], 'doc_count' => $result['aggregations']['pinned']['buckets'][$key]['doc_count']];
      }
      $result['aggregations']['pinned']['buckets'] = $pins;
    }
    if (!empty($result['aggregations']['temporal']['temporal']['buckets'] ?? null)) {
      $this->mergeRootCount($result['aggregations']['temporal']['temporal']['buckets']);
    } elseif (!empty($result['aggregations']['temporal']['temporal']['temporal']['buckets'] ?? null)) {
      $this->mergeRootCount($result['aggregations']['temporal']['temporal']['temporal']['buckets']);
    }
    return $this->resultToFrontend($result);
  }

  /**
   * Get data specific for mini map
   */
  public function getMiniMapData () {
    $query = $this->getCurrentQuery();
    unset($query['sort']);
    unset($query['from']);
    $query = $this->getMapQuery($query);
    $query['_source'] = ['title', 'spatial'];

    if ($query['size'] <= $this->settings->environment->mapMarkerThreshhold) {
      // mini map renders heatmap, query only aggs needed for heatmap
      $query['aggregations']['geogridCentroid'] = QuerySettings::getSearchAggregations()['geogridCentroid'];
    } else {
      // mini map renders markers, no aggs nedded because markers uses records spatial data
      unset($query['aggregations']);
    }

    return $this->resultToFrontend($this->elasticDoSearch($query));
  }

  /**
   * Get single record from Elastic db
   */
  public function getRecord ($recordId) {
    $searchParams = [
      'id' => Utils::escapeLuceneValue($recordId),
      'index' => $this->elasticEnv->index,
    ];

    $record = $this->elasticDoGet($searchParams);
    if (empty($record)) {
      die;
    }

    $record = $record['_source'];
    $record['id'] = $recordId;
    $record['similar'] = $this->getThematicallySimilarItems($record, $recordId);
    $record['nearby'] = $this->getNearbySpatialResources($record);
    $record['collection'] = $this->getCollectionItems($record);
    $record['partOf'] = $this->getItemsPartOf($record, $recordId);
    $record['isAboutResource'] = $this->getIsAboutResources($record);
    $record['periodo'] = $this->getPeriodsForRecord($record);

    return Utils::splitLanguages($record, $this->getDefaultLanguage());
  }

  /**
   * Gets autocomplete values
   */
  public function autocomplete () {
    $q = strtolower(trim($_GET['q'] ?? ''));

    if (!$q) {
      return null;
    }

    $fields = trim($_GET['fields'] ?? '');
    $isAllFields = empty($fields) || $fields === 'all';

    if ($fields !== 'aatSubjects') {
      $q = sprintf('%1$s | %1$s*', Utils::escapeLuceneValue($q));
      $innerQuery = [];
      $nested = [];
      $fieldTypes = $isAllFields ? [
        'ariadneSubject.prefLabel.raw',
        'country.name.raw',
        'dataType.label.raw',
        'derivedSubject.prefLabel.raw',
        'description.text.raw',
        'nativeSubject.prefLabel.raw',
        'ariadneSubject.prefLabel^2',
        'country.name^2',
        'dataType.label^2',
        'derivedSubject.prefLabel^2',
        'description.text^2',
        'nativeSubject.prefLabel^2',
      ] : [];
      if ($isAllFields || $fields === 'title') {
        $fieldTypes[] = 'title.text.raw^3';
        $fieldTypes[] = 'title.text^4';
      }
      if (!empty($fieldTypes)) {
        $innerQuery['bool']['should'][] = [
          'simple_query_string' => [
            'default_operator' => 'and',
            'fields' => $fieldTypes,
            'query' => $q,
          ],
        ];
      }
      if ($isAllFields || $fields === 'location') {
        $nested['spatial'] = [
          'spatial.placeName.raw',
          'spatial.placeName^2'
        ];
      }
      if ($isAllFields || $fields === 'time') {
        $nested['temporal'] = [
          'temporal.periodName.raw',
          'temporal.periodName^2'
        ];
      }
      foreach ($nested as $key => $val) {
        $innerQuery['bool']['should'][] = [
          'nested' => [
            'path' => $key,
            'query' => [
              'simple_query_string' => [
                'default_operator' => 'and',
                'fields' => $val,
                'query' => $q,
              ],
            ],
          ],
        ];
      }
      $query = [
        '_source' => ['title'],
        'query' => $innerQuery,
        'highlight' => [
          'fields' => [
            '*' => new \stdClass()
          ],
        ],
      ];

      $search = $this->elasticDoSearch($query);

      // set if there are more total results > size
      $result['hasMoreResults'] = $search['hits']['total']['value'] > count($search['hits']['hits']);

      // loop and set hits
      $result['hits'] = [];
      foreach ($search['hits']['hits'] as $key => $value) {

        $nValue = Utils::splitLanguages($value['_source'], $this->getDefaultLanguage());
        $result['hits'][$key] = [
          'id' => $value['_id'],
          'label' => $nValue['title'],
        ];

        // get all fields where search string ($q) was found
        foreach ($value['highlight'] as $hKey=>$hValue) {
          if (str_contains($hKey, '.')) {
            // delete all after .
            $hKey = strstr($hKey,'.',true);
          }
          $result['hits'][$key]['fieldHits'][] = $hKey;
        }

        // remove duplicates from fieldHits
        $result['hits'][$key]['fieldHits'] = array_unique($result['hits'][$key]['fieldHits']);

      }

      return $result;

    } else {

      // Search AAT-subjects
      $query = [
        '_source' => ['prefLabel', 'prefLabels'],
        'size' => 10,
        'query' => [
          'nested' => [
            'path' => 'prefLabels',
            'query' => [
              'bool' => [
                'must' => [
                  ['match_phrase_prefix' => ['prefLabels.label' => Utils::escapeLuceneValue($q) . '*']]
                ],
              ],
            ],
          ],
        ],
      ];

      $search = $this->elasticDoSearch($query, $this->elasticEnv->subjectIndex);

      // set if there are more total results > size
      $result['hasMoreResults'] = $search['hits']['total']['value'] > count($search['hits']['hits']);

      // set hits
      $result['hits'] = [];

      foreach ($search['hits']['hits'] as $hit) {
        $label = $hit['_source']['prefLabel'];
        $variants = [];

        if (!empty($hit['_source']['prefLabels'])) {
          foreach ($hit['_source']['prefLabels'] as $variant) {
            if ($variant['label'] !== $label) {
              $variants[] = $variant;
            }
          }
        }

        $result['hits'][] = [
          'id' => $hit['_id'],
          'label' => $label,
          'variants' => $variants,
        ];
      }
      return $result;
    }
    return null;
  }

  /**
   * Gets autocomplete filters
   */
  public function autocompleteFilter () {
    $q = Utils::escapeLuceneValue($_GET['filterQuery'] ?? '');
    $filterName = trim($_GET['filterName'] ?? '');
    $query = null;

    if ((!$q && empty($_GET['filterSize'])) || !$filterName) {
      return null;
    }

    if (isset($_GET[$filterName])) {
      unset($_GET[$filterName]);
    }
    if (isset($_GET['bbox'])) {
      unset($_GET['bbox']);
    }

    $currentQuery = $this->getCurrentQuery()['query'];

    $size = intval($_GET['filterSize'] ?? 0);
    $size = is_int($size) ? ($size * 20) + 20 : 20;

    switch (strtolower($filterName)) {
      case 'contributor':
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
            'unique_agg_count' => ['cardinality' => ['field' => 'contributor.name.raw']]
          ],
        ];
        if ($q) {
          $query['aggregations']['filtered_agg']['terms']['include'] = $this->getIncludeRegexp($q);
        }
        break;

      case 'country':
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
            'unique_agg_count' => ['cardinality' => ['field' => 'country.name.raw']]
          ],
        ];
        if ($q) {
          $query['aggregations']['filtered_agg']['terms']['include'] = $this->getIncludeRegexp($q);
        }
        break;

      case 'datatype':
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
            'unique_agg_count' => ['cardinality' => ['field' => 'dataType.label.raw']]
          ]
        ];
        if ($q) {
          $query['aggregations']['filtered_agg']['terms']['include'] = $this->getIncludeRegexp($q);
        }
        break;

      case 'nativesubject':
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
            'unique_agg_count' => ['cardinality' => ['field' => 'nativeSubject.prefLabel.raw']
            ]
          ],
        ];
        if ($q) {
          $query['aggregations']['filtered_agg']['terms']['include'] = '(.*' . strtolower($q) . '.*)';
        }
        break;

      case 'ariadnesubject':
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
            'unique_agg_count' => ['cardinality' => ['field' => 'ariadneSubject.prefLabel.raw']]
          ],
        ];
        if ($q) {
          $query['aggregations']['filtered_agg']['terms']['include'] = $this->getIncludeRegexp($q);
        }
        break;

      case 'derivedsubject':
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
            'unique_agg_count' => ['cardinality' => ['field' => 'derivedSubject.prefLabel.raw']]
          ],
        ];
        if ($q) {
          $query['aggregations']['filtered_agg']['terms']['include'] = '(.*' . strtolower($q) . '.*)';
        }
        break;

      case 'publisher':
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
            'unique_agg_count' => ['cardinality' => ['field' => 'publisher.name.raw']]
          ],
        ];
        if ($q) {
          $query['aggregations']['filtered_agg']['terms']['include'] = $this->getIncludeRegexp($q);
        }
        break;

      case 'temporal':
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
                'unique_agg_count' => ['cardinality' => ['field' => 'temporal.periodName.raw']]
              ]
            ]
          ],
        ];
        if ($q) {
          $query['aggregations']['temporal_agg']['aggs']['filtered_agg']['terms']['include'] = '(.*' . strtolower($q) . '.*)';
        }
        return $this->elasticDoSearch($query, $this->elasticEnv->index)['aggregations']['temporal_agg'];

      case 'temporalregion':
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
          $query['aggregations']['filtered_agg']['terms']['include'] = '(' . strtolower($q). '.*)';
        }
        return $this->elasticDoSearch($query, $this->elasticEnv->periodIndex)['aggregations'];

      case 'culturalperiods': // Special for periods is periodCountry param to filter on user selected country
        $temporalRegion = trim($_GET['temporalRegion'] ?? '');
        $filterRegionQuery = null;
        foreach (explode('|', $temporalRegion) as $region) {
          if (empty($region)) {
            $filterRegionQuery['bool']['should'] = ['match_all' => new \stdClass()];
            break;
          }
          $filterRegionQuery['bool']['should'][] = ['term' => ['spatialCoverage.label.raw' => Utils::escapeLuceneValue($region)]];
        }
        $query['size'] = $size;
        $query['sort'] = ['start.year' => ['order' => 'asc']];
        $query['query']['bool']['must'] = [
          'nested' => [
            'path' => 'localizedLabels',
            'query' => [
              'bool' => [
                'must' => [
                  ['wildcard' => ['localizedLabels.label.raw' => $q . '*']],
                  ['match' => ['localizedLabels.language' => 'en']]
                ]
              ]
            ]
          ]
        ];
        if ($filterRegionQuery) {
          $query['query']['bool']['filter'] = $filterRegionQuery;
        }
        return $this->periodsToAggs($this->elasticDoSearch($query, $this->elasticEnv->periodIndex));

      default:
        return null;
    }

    return $this->elasticDoSearch($query, $this->elasticEnv->index)['aggregations'];
  }

  /**
   * Build regular expression for include clause in Elastic query
   */
  private function getIncludeRegexp ($q) {
    $q = preg_split('/[\s]+/', $q);
    $regexpInclude = '';
    foreach ($q as $key => $value) {
      $regexpInclude .= '(.*' . strtolower($value) . '.*|.*' . ucfirst($value) . '.*|.*' . strtoupper($value) . '.*)';
    }
    return $regexpInclude;
  }


  /**
   * Special for Periods autocompletion.
   * Disguise query response as an aggregation formated array before returning
   * to frontend since the frontend Aggregation filter can only handle aggregations.
   */
  private function periodsToAggs ($periodsResult) {
    $buckets = [];

    if (!empty($periodsResult['hits']['hits'])) {
      foreach ($periodsResult['hits']['hits'] as $periodKey => $period) {
        $bucket = [];
        $bucket['key'] = $period['_id'];
        $bucket['region'] = $period['_source']['spatialCoverage'][0]['label'] ?? '';
        $bucket['start'] = ($period['_source']['start']['year'] ?? 0) +0; // for sorting
        $bucket['filterLabel'] = $period['_source']['label'];
        $bucket['doc_count'] = $period['_source']['total'];

        if (!empty($period['_source']['localizedLabels']) && ($period['_source']['languageTag'] !== 'en' || !$bucket['filterLabel'])) {
          foreach (array_reverse($period['_source']['localizedLabels']) as $loc) {
            if ($loc['language'] === 'en') {
              $bucket['filterLabel'] = $loc['label'];
              if ($bucket['filterLabel'] === $period['_source']['label']) {
                break;
              }
            }
          }
        }
        $bucket['filterLabel'] = $bucket['filterLabel'] ?: 'Unknown';

        if (!empty($period['_source']['timestamp'])) {
          $time = intval($period['_source']['timestamp'] ?? 0);
          if ($time && $time < time()) {
            $bucket['hasUpdate'] = true;
          }
        }

        $bucket['timespan'] = ($period['_source']['start']['year'] ?? 0) . ', ' . ($period['_source']['stop']['year'] ?? 0); // poc/test

        $bucket['extraLabels']['start'] = $period['_source']['start']['label'] . ' (Year: ' . ($period['_source']['start']['year'] ?? 'N/A'). ') ';
        $bucket['extraLabels']['stop'] = $period['_source']['stop']['label'] . ' (Year: ' . ($period['_source']['stop']['year'] ?? 'N/A') . ')';
        $bucket['extraLabels']['nativePeriodName'] = $period['_source']['label'] ?? '';
        $bucket['extraLabels']['authority'] = $period['_source']['authority'] ?? '';

        if (!empty($period['_source']['localizedLabels'])) {
          $localLabels = '';
          foreach ($period['_source']['localizedLabels'] as $label) {
            $localLabels .= $label['label'] . ' ('. $label['language'] . '), ';
          }
          $bucket['extraLabels']['localizedLabels'] = trim($localLabels, ', ');
        }
        if (!empty($period['_source']['spatialCoverage'])) {
          $spatials = '';
          foreach ($period['_source']['spatialCoverage'] as $spat) {
            $spatials .= $spat['label'] . ', ';
          }
          $bucket['extraLabels']['region'] = trim($spatials, ', ');
        }
        $buckets[] = $bucket;
      }
    }

    $aggs['filtered_agg']['buckets'] = $buckets;

    $size = intval($_GET['filterSize'] ?? 0);
    $size = is_int($size) ? ($size * 20) + 20 : 20;
    $aggs['filtered_agg']['sum_other_doc_count'] = !$size || $size < $periodsResult['hits']['total']['value'] ? $periodsResult['hits']['total']['value'] : 0;
    return $aggs;
  }

  /**
   * Replaces buckets doc_count with root_count if any
   */
  private function mergeRootCount (&$buckets) {
    foreach ($buckets as $key => $val) {
      if (isset($buckets[$key]['root_count']['doc_count'])) {
        $buckets[$key]['doc_count'] = $buckets[$key]['root_count']['doc_count'] ?? $buckets[$key]['doc_count'];
        unset($buckets[$key]['root_count']);
      }
    }
  }

  /**
   * Frontend wants data in a specifik form.
   */
  private function resultToFrontend ($result) {
    // Probably the only error message we want to pass on to frontend
    if (isset($result['error']['message'])) {
      $jsonMsg = json_decode($result['error']['message']);
      if (isset($jsonMsg->error->root_cause[0]->reason)) {
        if (str_starts_with($jsonMsg->error->root_cause[0]->reason, 'Result window is too large')) {
          return [
            'error' => [
              'msg' => "Scrolling exceeded maximum"
            ]
          ];
        }
      }
      return [];
    }

    $hits = [];
    if (!empty($result['hits']['hits'])) {
      foreach ($result['hits']['hits'] as $hitMeta=>$hit) {
        $hits[] = [
          'id' => $hit['_id'],
          'data' => Utils::splitLanguages($hit['_source'], $this->getDefaultLanguage()),
        ];
      }
    }
    return [
      'total' => $result['hits']['total'] ?? 0,
      'hits' => $hits,
      'aggregations' => $result['aggregations'] ?? [],
    ];
  }

  /**
   * Handle sorting.
   */
  private function getSort () {
    if (!empty($_GET['sort'])) {
      $sort = QuerySettings::getSearchSort()[$_GET['sort']] ?? null;
      if ($sort) {
        $ret = [
          $sort['key'] => ['order' => ($_GET['order'] ?? '') === 'desc' ? 'desc' : 'asc']
        ];
        if ($sort['nested']) {
          $ret[$sort['key']]['nested']['path'] = $sort['nested'];
        }
        return $ret;
      }
    }
    return [
      '_score' => ['order' => 'desc']
    ];
  }

  /**
   * Paging
   */
  private function getFrom () {
    $from = 0;
    if (!empty($_GET['page'])) {
      $from = intval($_GET['page']);
      if (!$from || $from < 2) {
        $from = 0;
      } else {
        $from = ($from - 1) * $this->getSize();
      }
    }
    return $from;
  }

  /**
   * Size - amount of posts per page
   */
  private function getSize () {
    $size = intval($_GET['size'] ?? 20);
    return min(max($size, 0), 50);
  }

  /**
   * Get spatial nearby from given record
   */
  public function getNearbySpatialResources ($record) {
    return GeoUtils::getNearbyResources($record);
  }

  /**
   * Get all matching resources for is_about.uri for given record
   */
  private function getIsAboutResources ($record) {
    if (empty($record['is_about'])) {
      return [];
    }

    $parts = null;
    foreach ($record['is_about'] as $isAboutUri) {
        $parts[] = [
          'match_phrase' => ['is_about.uri' => $isAboutUri['uri']]
        ];
    }

    if (($parts == null)) {
      return [];
    }

    $params = [
      '_source' => ['title'],
      'query' => [
        'bool' => [
          'must_not' => [
            'term' => ['_id' => $record['id'] ]
          ],
          'filter' => [
            'bool' => [
              'should' => $parts
            ]
          ]
        ]
      ]
    ];

    $result = $this->elasticDoSearch($params)['hits']['hits'];
    $ret = [];

    foreach ($result as $hit) {
      $nHit = Utils::splitLanguages($hit['_source'], $this->getDefaultLanguage());
      $ret[] = [
        'id' => $hit['_id'],
        'title' => $nHit['title'] ?? [],
      ];
    }
    return $ret;
  }

  /**
   * Returns all periods for a single record
   */
  private function getPeriodsForRecord ($record) {
    if (empty($record['temporal'])) {
      return null;
    }
    $periods = [];
    foreach ($record['temporal'] as $temporal) {
      $arr = explode('/', $temporal['uri'] ?? '');
      if (in_array('n2t.net', $arr)) {
        $periods[] = [
          'match' => [
            'id' => Utils::escapeLuceneValue(end($arr)),
          ],
        ];
      }
    }
    if (empty($periods)) {
      return null;
    }
    return $this->periodsToAggs($this->elasticDoSearch([
      'query' => [
        'bool' => [
          'must' => [
            'bool' => [
              'should' => $periods,
            ],
          ],
        ],
      ],
    ], $this->elasticEnv->periodIndex))['filtered_agg']['buckets'] ?? null;
  }

  /**
   * Gets thematically similar items for a record
   */
  private function getThematicallySimilarItems ($record, $recordId) {
    $matches = [];
    $type = $_GET['thematical'] ?? '';

    if ($type === 'title') {
      if (!empty($record['title'])) {
        foreach($record['title'] as $title) {
          $matches[] = [
            'match' => [
              'title.text' => Utils::escapeLuceneValue($title['text'])
            ]
          ];
        }
      }
    } elseif ($type === 'location') {

      if (!empty($record['spatial'])) {
        $spatialMatches = [];
        foreach ($record['spatial'] as $spatial) {
          if (!empty($spatial['placeName'])) {
            $spatialMatches[] = [
              'match' => [
                'spatial.placeName' => str_replace(["\r", "\n", "\t", "\v"], '', $spatial['placeName'])
              ]
            ];
          }
        }
        if(!empty($spatialMatches)) {
          $matches = [
            'nested' => [
              'path' => 'spatial',
              'query' => [
                'bool' => [
                  'should' => is_array($spatialMatches) ? $spatialMatches : [$spatialMatches]
                ]
              ]
            ]
          ];
        }
      }

    } elseif ($type === 'subject') {
      if (!empty($record['nativeSubject'])) {
        foreach ($record['nativeSubject'] as $subject) {
          if (!empty($subject['prefLabel'])) {
            $matches[] = [
              'match' => [
                'nativeSubject.prefLabel' => str_replace(["\r", "\n", "\t", "\v"], '', $subject['prefLabel']),
              ],
            ];
          }
        }
      }
    } elseif ($type === 'temporal') {

      if (!empty($record['temporal'])) {
        $temporalMatches = [];
        foreach ($record['temporal'] as $temporal) {
          if (!empty($temporal['periodName'])) {
            $temporalMatches[] = [
              'match' => [
                'temporal.periodName.raw' => str_replace(["\r", "\n", "\t", "\v"], '', $temporal['periodName'])
              ]
            ];
          }
        }
        if(!empty($temporalMatches)) {
          $matches = [
            'nested' => [
              'path' => 'temporal',
              'query' => [
                'bool' => [
                  // temporary fix - was before 'should' => [ $temporalMatches ] - but now started crashing
                  'should' => is_array($temporalMatches) ? $temporalMatches : [$temporalMatches],
                ]
              ]
            ]
          ];
        }
      }
    } else { // default - subject & temporal

      if (!empty($record['nativeSubject'])) {
        foreach ($record['nativeSubject'] as $subject) {
          if (!empty($subject['prefLabel'])) {
            $matches[] = [
              'match' => [
                'nativeSubject.prefLabel' => str_replace(["\r", "\n", "\t", "\v"], '', $subject['prefLabel']),
              ],
            ];
          }
        }
      }

      if (!empty($record['temporal'])) {

        $temporalMatches = [];
        foreach ($record['temporal'] as $temporal) {
          if (!empty($temporal['periodName'])) {
            $temporalMatches[] = [
              'match' => [
                'temporal.periodName.raw' => str_replace(["\r", "\n", "\t", "\v"], '', $temporal['periodName'])
              ]
            ];
          }
        }
        if(!empty($temporalMatches)) {
          $matches = [
            'nested' => [
              'path' => 'temporal',
              'query' => [
                'bool' => [
                  // temporary fix - was before 'should' => [ $temporalMatches ] - but now started crashing
                  'should' => is_array($temporalMatches) ? $temporalMatches : [$temporalMatches],
                ]
              ]
            ]
          ];
        }
      }
    }

    if (empty($matches)) {
      return [];
    }

    $params = [
      '_source' => ['title', 'ariadneSubject'],
      'size' => 7,
      'query' => [
        'bool' => [
          'must_not' => [
            'match' => [
              '_id' => $recordId
            ]
          ],
          'should' => $matches,
          'minimum_should_match' => 1
        ]
      ]
    ];

    $result = $this->elasticDoSearch($params)['hits']['hits'];
    $ret = [];

    foreach ($result as $res) {
      $nRes = Utils::splitLanguages($res['_source'], $this->getDefaultLanguage());
      $ret[] = [
        'id' => $res['_id'],
        'type' => $res['_source']['ariadneSubject'] ?? null,
        'title' => $nRes['title'] ?? [],
      ];
    }
    return $ret;
  }

  /**
   * Returns a list of items/collections a record is included in
   */
  private function getItemsPartOf ($record, $recordId) {
    if (empty($record['isPartOf'])) {
      return null;
    }

    $parts = [];
    foreach ($record['isPartOf'] as $part) {
      $parts[] = ['match' => ['identifier' => $part]];
    }

    if (empty($parts)) {
      return null;
    }

    $params = [
      '_source' => ['title'],
      'query' => [
        'bool' => [
          'should' => $parts
        ]
      ]
    ];

    $result = $this->elasticDoSearch($params)['hits']['hits'];
    $res = [];

    foreach ($result as $hit) {
      $nHit = Utils::splitLanguages($hit['_source'], $this->getDefaultLanguage());
      $res[] = [
        'id' => $hit['_id'],
        'title' => $nHit['title'] ?? '',
      ];
    }
    return $res;
  }

  /**
   * Gets a records collection items, and total value
   */
  private function getCollectionItems ($record) {
    if (empty($record['resourceType']) || $record['resourceType'] !== 'collection') {
      return [];
    }

    $params = [
      '_source' => ['title'],
      'size' => 7,
      'query' => [
        'match_phrase' => [
          'isPartOf' => $record['identifier']
        ]
      ]
    ];

    $result = $this->elasticDoSearch($params);
    $total = intval($result['hits']['total']['value'] ?? 0);
    $hits = [];

    if ($total > 0) {
      foreach ($result['hits']['hits'] as $hit) {
        $nHit = Utils::splitLanguages($hit['_source'], $this->getDefaultLanguage());
        $hits[] = [
          'id' => $hit['_id'],
          'title' => $nHit['title'] ?? [],
        ];
      }
    }

    return [
      'total' => $total,
      'hits' => $hits
    ];
  }

  /**
   * Gets info about a single aat subject
   */
  public function getSubject ($id) {
    if (!is_numeric($id)) { // if not an id - try to find the id matching the title in ariadne index
      $title = Utils::escapeLuceneValue(urldecode($id));
      $derivedSubjects = $this->elasticDoSearch([
        '_source' => ['derivedSubject'],
        'size' => 1,
        'query' => [
          'bool' => [
            'must' => [
              'match' => [
                'derivedSubject.prefLabel.raw' => $title,
              ],
            ],
          ],
        ],
      ])['hits']['hits'][0]['_source']['derivedSubject'] ?? null;

      if ($derivedSubjects) {
        foreach ($derivedSubjects as $sub) {
          if ($sub['prefLabel'] === $title) {
            $id = explode('/', $sub['id']);
            $id = end($id);
          }
        }
      }
    }

    if (!$id) {
      return null;
    }

    $subject = $this->elasticDoGet([
      'id' => $id,
      'index' => $this->elasticEnv->subjectIndex,
    ]);

    $subject = $subject['_source'];
    $subject['id'] = $id;
    $subject['subSubjects'] = $this->getSubSubjects($id);

    return $subject;
  }

  /**
   * Returns subjects sub subjects
   */
  public function getSubSubjects ($id) {
    $params = [
      '_source'=> ['prefLabel'],
      'size' => 100,
      'query' => [
        'bool' => [
          'must' => [
            'term' => [
              'broader.id'=>[
                'value' => $id,
              ],
            ],
          ],
        ],
      ],
    ];

    $subs = $this->elasticDoSearch($params, $this->elasticEnv->subjectIndex)['hits']['hits'] ?? [];
    $ret = [];

    foreach ($subs as $sub) {
      if (!empty($sub['_source']['prefLabel'])) {
        $ret[] = [
          'id' => $sub['_id'],
          'prefLabel' => $sub['_source']['prefLabel'],
        ];
      }
    }
    return $ret;
  }

  // Returns all period regions
  // Get countries aggregation query from periods index
  public function getPeriodRegions () {
    $query = [
      'size' => 0,
      'aggregations' => [
        'periodCountry' => [
          'terms' => [
            'field' => 'spatialCoverage.label.raw',
            'order' => [ '_count' => 'desc' ],
            'size' => 20,
          ],
        ],
      ],
    ];
    return $this->resultToFrontend($this->elasticDoSearch($query, $this->elasticEnv->periodIndex));
  }

  // Get periods for country - default any countries
  public function getPeriodsForCountry () {
    $temporalRegion = trim($_GET['temporalRegion'] ?? '');
    $query = [
      '_source' => ['authority', 'label', 'languageTag', 'spatialCoverage', 'localizedLabels', 'start', 'stop', 'total', 'timestamp'],
      'size' => 20,
      'sort' => ['start.year' => ['order' => 'asc']],
    ];
    if (!$temporalRegion) {
      $query['query']['bool']['must'] = ['match_all' => new \stdClass()];
    } else {
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
    }
    return $this->periodsToAggs($this->elasticDoSearch($query, $this->elasticEnv->periodIndex)); // disguise as aggregations
  }

  // Automatically update periods once a day
  public function maybeUpdatePeriods () {
    $result = $this->elasticDoSearch([
      'size' => 1,
      '_source' => ['timestamp'],
      'query' => [
        'match_all' => new \stdClass(),
      ],
    ], $this->elasticEnv->periodIndex);

    $time = intval($result['hits']['hits'][0]['_source']['timestamp'] ?? 0);
    if ($time && $time < time()) {
      new Periodo(true);
    }
  }

  // Returns all services and publishers
  public function getServicesAndPublishers () {
    $params = [ 'size' => 10000 ];
    $services = $this->elasticDoSearch($params, $this->elasticEnv->servicesIndex)['hits']['hits'] ?? [];
    $publishers = $this->elasticDoSearch($params, $this->elasticEnv->publishersIndex)['hits']['hits'] ?? [];
    return [
      'services' => array_map(function ($s) { return $s['_source']; }, $services),
      'publishers' => array_map(function ($p) { return $p['_source']; }, $publishers),
    ];
  }

  // Returns all global no format strings from publishers
  public function getNoFormats () {
    $ret = [];
    if (!empty($_GET['publishers'])) {
      $parts = [];
      foreach (explode('|', $_GET['publishers']) as $publisher) {
        $parts[] = [
          'match' => [
            'publisher.name.raw' => Utils::escapeLuceneValue($publisher),
          ],
        ];
      }
      $query = [
        'size' => 0,
        'query' => [
          'bool' => [
            'must' => [
              'bool' => [
                'should' => $parts,
              ],
            ],
          ],
        ],
        'aggregations' => [
          'subject' => [
            'terms' => [
              'size' => 10000,
              'field' => 'nativeSubject.prefLabel.raw',
            ],
          ],
          'temporal' => [
            'nested' => [ 'path' => 'temporal'],
            'aggs' => [
              'temporal' => [
                'terms' => [
                  'size' => 10000,
                  'field' => 'temporal.periodName.raw',
                ],
              ],
            ],
          ],
        ],
      ];
      $res = $this->elasticDoSearch($query);
      foreach (array_keys($query['aggregations']) as $key) {
        foreach (($res['aggregations'][$key][$key]['buckets'] ?? $res['aggregations'][$key]['buckets']) as $item) {
          $ret[] = $item['key'];
        }
      }
    }
    return $ret;
  }

  /**
   * Get total records count in main index
   */
  public function getTotalRecordsCount () {
    try {
      return $this->getClient()->count(['index' => $this->elasticEnv->index])['count'] ?? 0;
    } catch (\Exception $ex) {
      if (AppSettings::isLogging()) {
        AppSettings::debugLog($ex->getMessage());
      }
    }
    return 0;
  }

  /**
   * Get from Elastic host db
   */
  private function elasticDoGet ($searchParams) {
    try {
      $result = $this->getClient()->get($searchParams);
      if (AppSettings::isLogging()) {
        AppSettings::debugLog('Request URI: '. $_SERVER['REQUEST_URI']);
        AppSettings::debugLog(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] . ' - ' . json_encode($searchParams, JSON_UNESCAPED_SLASHES));
      }
      return $result;

    } catch (\Exception $e) {
      if (AppSettings::isLogging()) {
        AppSettings::debugLog($e->getMessage());
        AppSettings::debugLog(json_encode($searchParams, JSON_UNESCAPED_SLASHES));
      }
      exit;
    }
  }

  /**
   * Search Elastic host db
   */
  public function elasticDoSearch ($searchParams, $index = null) {
    $searchParams['track_total_hits'] = true;
    $params = [
      'index' => $index ?: $this->elasticEnv->index,
      'body'  => $searchParams,
    ];

    try {
      $result = $this->getClient()->search($params);
      if (AppSettings::isLogging()) {
        AppSettings::debugLog('Request URI: '. $_SERVER['REQUEST_URI']);
        AppSettings::debugLog(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] . ' - ' . json_encode($searchParams, JSON_UNESCAPED_SLASHES));
      }
      return Utils::normalizeAggs($result, $this->aggregationsReqFilter);

    } catch (\Exception $e) {
      if (AppSettings::isLogging()) {
        AppSettings::debugLog($e->getMessage());
        AppSettings::debugLog(json_encode($searchParams, JSON_UNESCAPED_SLASHES));
      }
      return [
        'error' => [
          'code' => $e->getCode(),
          'message'=> $e->getMessage()
        ]
      ];
    }
  }
}
