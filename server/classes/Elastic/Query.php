<?php

namespace Elastic;

use Application\AppSettings;
use Elastic\QuerySettings;
use Elastic\Timeline;
use Elastic\Utils;
use Elastic\GeoUtils;
use Elastic\DataNormalizer;
use Elasticsearch\ClientBuilder;
use Periodo\Periodo;

class Query {
  private static $inst = null;
  private $settings;
  private $normalizer;
  private $elasticEnv;
  private $client = null;
  private $aggregationsReqFilter = []; // Aggregation filters from uri

  public function __construct() {
    $this->settings = AppSettings::getSettings();
    $this->normalizer = new DataNormalizer($this->settings);
    $this->elasticEnv = AppSettings::getSettingsEnv();
  }

  // returns instance of class (singleton)
  public static function instance() {
    if (!self::$inst) {
      self::$inst = new Query();
    }
    return self::$inst;
  }

  // sets client host
  public function setClient($clientHost) {
    // Setup Elastic client
    $this->client = ClientBuilder::create()
    ->setHosts([$clientHost])
    ->build();
  }

  // lazy get client - default env host if not called "setClient" before
  public function getClient() {
    if (!$this->client) {
      $this->setClient($this->elasticEnv->host);
    }
    return $this->client;
  }

  /**
   * Search Elastic db with given URI parameters
   */
  public function search() {
    $query = $this->getCurrentQuery();

    if (isset($_GET['mapq'])) {
      // This is the Map requesting data. Push map specific query attributes to main
      $query = $this->getMapQuery($query);

      // When size is smaler than threshhold, geogrid is considered part of the result since we need to render heatmap
      if( intval($query['size']) <= $this->settings->environment->mapMarkerThreshhold ) {
        $query['aggregations']['geogrid_centroid'] = QuerySettings::getSearchAggregations()['geogrid_centroid'];
      }
    }

    $result = $this->elasticDoSearch($query);

    return $this->resultToFrontend($result);
  }

  /**
   * Build main elastic consumable query from/with GET params
   * Notice, this is the main body query, aggregations excluded!
   */
  private function getCurrentQuery () {
    $query['size'] = $this->getSize();
    $query['from'] = $this->getFrom();
    $query['sort'] = $this->getSort();

    $searchInField = null;
    $filters = [];
    $innerQuery = [];

    if(isset($_GET['fields'])) {
      $searchInField = trim($_GET['fields']);
    }

    if (isset($_GET['q'])) {
      $_GET['q'] = trim($_GET['q']);
    }

    // Handle incoming user input query string
    if (!empty($_GET['q']) ) {
      $validFields = QuerySettings::getValidSearchableFields(Utils::escapeLuceneValue($_GET['q'], false));
      if($searchInField && array_key_exists($searchInField, $validFields) ) {
        $innerQuery['bool']['must'] = $validFields[$searchInField]['query'];
      } else {
        $innerQuery['bool']['must'][] = QuerySettings::getMultiMatchQuery(Utils::escapeLuceneValue($_GET['q']));
      }
    } else {
      $innerQuery['bool']['should'] = array('match_all'=> new \stdClass());
    }

    // push inner query to main query
    $query['query'] = $innerQuery; // Attach inner query

    // Push filters
    $filters = QuerySettings::getFilters($_GET);

    // Push filters to main query
    foreach ($filters as $filter) {
      $query['query']['bool']['filter'][] = $filter;
    }

    return $query;
  }

  /**
   * Map specific query.
   * Adds additional map parameters to main query
   *
   * @param array $mainQuery Base query, containg filters, terms, aggregations and query string param.
   * @return array Query with additional map specific attributes
   */
  private function getMapQuery($mainQuery) {

    // Fetch only needed fields for map view
    $mainQuery['_source'] = ['title','description','resourceType','publisher','ariadneSubject','spatial'];

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
    $mainQuery['query']['bool']['filter'][] = [
      'nested' => [
        'path' => 'spatial',
        'query' => [
          'bool' => [
            'should' => [
              array( 'exists' => ['field'=> 'spatial.geopoint']), // remove when centroids are uploaded to public
              array( 'exists' => ['field'=> 'spatial.polygon']), // remove when centroids are uploaded to public
              array( 'exists' => ['field'=> 'spatial.boundingbox']), // remove when centroids are uploaded to public
              array( 'exists' => ['field'=> 'spatial.centroid'])
            ]
          ]
        ]
      ]
    ];

    /* Do roundtrip to ES to see if result is more than 500. If result count is more
       than 500 the map doesn't need records data because it's rendering heatmap with
       data from aggregations->geogrid */
    $mainQuery['size'] = $this->getMapResultSize($mainQuery);

    return $mainQuery;

  }

  /**
   * Get map result size.
   * Does an extra query roundtrip to backend to calculate the record size needed for map data.
   *
   * @param array Query params
   * @return int Map specific query record size
   */
  private function getMapResultSize(&$query): int {
    $count = $this->getResultCount($query);
    if( $count <= $this->settings->environment->mapMarkerThreshhold ) {
      return $count; // Render markers - records needed to render markers
    }
    return 0; // Render heatmap, no records needed
  }

  /**
   * Get data specific for aggregations / filters
   *
   * @param array Query params
   * @return array
   */
  public function getSearchAggregationData() {

    // get main query
    $query = $this->getCurrentQuery();

    // Push aggregation to main query
    $query['aggregations'] = QuerySettings::getSearchAggregations();

    // This is the map search requesting data. Push map specific queri attributes to main query
    if (isset($_GET['mapq'])) {
      $query = $this->getMapQuery($query);
    }

    // No records needed for aggregations
    $query['size'] = 0;

    // Remove unnessesary attributes
    unset($query['_source']);
    unset($query['sort']);
    unset($query['from']);
    unset($query['aggregations']['geogrid']);

    $range = empty($_GET['range']) ? null : explode(',', $_GET['range']);

    // timeline specific query
    if( isset($_GET['timeline']) && filter_var($_GET['timeline'], FILTER_VALIDATE_BOOLEAN) ) {
      $query['aggregations']['range_buckets'] = Timeline::prepareRangeBucketsAggregation($range);
    }

    $result = $this->elasticDoSearch($query);
    return $this->resultToFrontend($result);

  }

  /**
   * Get data specific for mini map
   *
   * @param array Query params
   * @return array Mini map data
   */
  public function getMiniMapData() {

    // get main query
    $query = $this->getCurrentQuery();
    // push map specifik attributes to query
    $query = $this->getMapQuery($query);

    // remove unnessesary attributes
    // Minimap only needs title and spatial data
    $query['_source'] = ['title','spatial'];
    unset($query['sort']);

    if($query['size'] <= $this->settings->environment->mapMarkerThreshhold ) {
      // mini map renders heatmap, query only aggs needed for heatmap
      $query['aggregations']['geogrid_centroid'] = QuerySettings::getSearchAggregations()['geogrid_centroid'];
    } else {
      // mini map renders markers, no aggs nedded because markers uses records spatial data
      unset($query['aggregations']);
    }

    $result = $this->elasticDoSearch($query);
    return $this->resultToFrontend($result);

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

    return $this->normalizer->splitLanguages($record);
  }

  /**
   * Gets autocomplete values
   */
  public function autocomplete() {
    $fields = trim($_GET['fields'] ?? '');
    $isAllFields = empty($fields) || $fields === 'all';
    $q = trim($_GET['q'] ?? '');

    if (!$q) {
      return null;
    }

    if ($fields !== 'aatSubjects') {
      $innerQuery =  null;

      if ($isAllFields) {
        // fields to query on autocomplete "all"
        // in addition also below are added: "title", "location" & "time"
        $fieldTypes = [
          //'is_about' => 'label',
          'ariadneSubject' => 'prefLabel',
          //'contributor' => 'name',
          //'creator' => 'name',
          'derivedSubject' => 'prefLabel',
          'description' => 'text',
          //'nativePeriod' => 'periodName',
          'nativeSubject' => 'prefLabel',
          //'owner' => 'name',
          //'publisher' => 'name',
        ];
        foreach ($fieldTypes as $key => $val) {
          $innerQuery['bool']['should'][] = [
            'match_bool_prefix' => [
              ($key . '.' . $val) => Utils::escapeLuceneValue($q),
            ],
          ];
        }
      }
      if ($isAllFields || $fields == 'title') {
        $innerQuery['bool']['should'][] = [
          'match_bool_prefix' => [
            'title.text' => Utils::escapeLuceneValue($q),
          ],
        ];
      }
      if ($isAllFields || $fields == 'location') {
        $innerQuery['bool']['should'][] = [
          'nested' => [
            'path' => 'spatial',
            'query' => [
              'match_bool_prefix' => [
                'spatial.placeName' => Utils::escapeLuceneValue($q)
              ],
            ],
          ],
        ];
      }
      if ($isAllFields || $fields == 'time') {
        $innerQuery['bool']['should'][] = [
          'nested' => [
            'path' => 'temporal',
            'query' => [
              'match_bool_prefix' => [
                'temporal.periodName' => Utils::escapeLuceneValue($q)
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

        $nValue = $this->normalizer->splitLanguages($value['_source']);
        $result['hits'][$key] = [
          'id' => $value['_id'],
          'label' => $nValue['title'],
        ];

        // get all fields where search string ($q) was found
        foreach ( $value['highlight'] as $hKey=>$hValue ) {
          if( str_contains($hKey, '.') ) {
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
                  ['match_phrase_prefix' => ['prefLabels.label' => Utils::escapeLuceneValue($q).'*']]
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
  public function autocompleteFilter() {
    $q = Utils::escapeLuceneValue($_GET['filterQuery'] ?? '');
    $filterName = trim($_GET['filterName'] ?? '');
    $query = '';

    if ((!$q && empty($_GET['filterSize'])) || !$filterName) {
      return null;
    }

    $currentQuery = $this->getCurrentQuery()['query'];

    $size = intval($_GET['filterSize'] ?? 0);
    $size = is_int($size) ? ($size * 20) + 20 : 20;

    switch (strtolower($filterName)) {
      case 'contributor':
        $query = AutocompleteFilterQuery::contributor($q, $currentQuery, $size);
        break;

      case 'nativesubject':
        $query = AutocompleteFilterQuery::nativeSubject($q, $currentQuery, $size);
        break;

      case 'ariadnesubject':
        $query = AutocompleteFilterQuery::ariadneSubject($q, $currentQuery, $size);
        break;

      case 'derivedsubject':
        $query = AutocompleteFilterQuery::derivedSubject($q, $currentQuery, $size);
        break;

      case 'publisher':
        $query = AutocompleteFilterQuery::publisher($q, $currentQuery, $size);
        break;

      case 'temporal':
        $query = AutocompleteFilterQuery::temporal($q, $currentQuery, $size);
        return $this->elasticDoSearch($query, $this->elasticEnv->index)['aggregations']['temporal_agg'];

      case 'temporalregion':
        $query = AutocompleteFilterQuery::temporalRegion($q, $currentQuery, $size);
        $this->setClient($this->elasticEnv->periodHost);
        $result = $this->elasticDoSearch($query, $this->elasticEnv->periodIndex)['aggregations'];
        return $result;

      case 'culturalperiods':
        // Special for periods is periodCountry param to filter on user selected country
        $temporalRegion = trim($_GET['temporalRegion'] ?? '');
        $query = AutocompleteFilterQuery::periods($q, $temporalRegion, $size);
        $this->setClient($this->elasticEnv->periodHost);
        // Disguise result as aggregation.
        return $this->periodsToAggs($this->elasticDoSearch($query, $this->elasticEnv->periodIndex));

      default:
        return null;

    }

    return $this->elasticDoSearch($query, $this->elasticEnv->index)['aggregations'];
  }


  /**
   * Special for Periods autocompletion.
   * Disguise query response as an aggregation formated array before returning
   * to frontend since the frontend Aggregation filter can only handle aggregations.
   */
  private function periodsToAggs($periodsResult) {
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
   * Frontend wants data in a specifik form.
   *
   * @param array Result form ES to be formated
   * @return array Array formated according to frontend specs
   */
  private function resultToFrontend($result) {

    // Probably the only error message we want to pass on to frontend
    if(isset($result['ERROR']['message'])) {
      $jsonMsg = json_decode( $result['ERROR']['message'] );
      if(isset($jsonMsg->error->root_cause[0]->reason)) {
        if(str_starts_with($jsonMsg->error->root_cause[0]->reason,'Result window is too large')) {
          return [
            'ERROR' => [
              'msg' => "Scrolling exceeded maximum"
            ]
          ];
        }
      }
      return [];
    }

    $hits = [];
    foreach ($result['hits']['hits'] as $hitMeta=>$hit) {
      $hitNormalized = $this->normalizer->splitLanguages($hit['_source']);
      $hits[] = [
        'id' => $hit['_id'],
        'data' => $hitNormalized
      ];
    }
    return [
      'total' => $result['hits']['total'],
      'hits' => $hits,
      'aggregations' => $result['aggregations'] ?? [],
    ];

  }

  /**
   * Get result count for given query
   *
   * @param array Query params
   * @return int The total record count resulting with given query
   */
  private function getResultCount(&$query): int {
    $countQuery['size'] = 0;
    $countQuery['query'] = $query['query'];
    $result = $this->elasticDoSearch($countQuery);
    if($result['hits']['total']['value']) {
      return intval( $result['hits']['total']['value'] );
    }
    return 0;
  }

  /**
   * Handle sorting.
   */
  private function getSort () {
    if (isset($_GET['sort']) && in_array($_GET['sort'], QuerySettings::getSearchSort())) {
      $order = 'asc';
      if (isset($_GET['order']) && $_GET['order'] === 'desc') {
        $order = 'desc';
      }
      return [
        $_GET['sort'] => ['order' => $order]
      ];
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
   * http://localhost:8080/api/getNearbySpatialResources/CD236FB9-44EA-34C9-9231-C61B2BF13DDD
   */
  public function getNearbySpatialResources($record) {
    $gUtils = new GeoUtils($this);
    return $gUtils->getNearbyResources($record);
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
      $nHit = $this->normalizer->splitLanguages($hit['_source']);
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
    $this->setClient($this->elasticEnv->periodHost);
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
    } else if ($type === 'location') {

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

    } else if ($type === 'subject') {
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
    } else if ($type === 'temporal') {

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
      $nRes = $this->normalizer->splitLanguages($res['_source']);
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
      return [];
    }

    $parts = [];
    foreach ($record['isPartOf'] as $part) {
      /*if (is_string($part)) {
        $part = explode('/', $part);
        $part = end($part);
      }*/
      //$parts[] = ['match' => ['_id' => $part]];
      $parts[] = ['match' => ['identifier' => $part]];
    }

    if (empty($parts)) {
      return null;
    }

    $params = [
      '_source' => ['title'],
      'query' => [
        'bool' => [
          'must' => $parts
        ]
      ]
    ];

    $result = $this->elasticDoSearch($params)['hits']['hits'];
    $res = [];

    foreach ($result as $hit) {
      $nHit = $this->normalizer->splitLanguages($hit['_source']);
      $res[] = [
        'id' => $hit['_id'],
        'title' => $nHit['title'] ?? [],
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
        $nHit = $this->normalizer->splitLanguages($hit['_source']);
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

  // Get method for normalizer (used in geoutils)
  public function getNormalizer() {
    return $this->normalizer;
  }

  // Returns all period regions
  // Get countries aggregation query from periods index
  public function getPeriodRegions() {
    $this->setClient($this->elasticEnv->periodHost);
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
  public function getPeriodsForCountry() {
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
    $this->setClient($this->elasticEnv->periodHost);
    return $this->periodsToAggs($this->elasticDoSearch($query, $this->elasticEnv->periodIndex)); // disguise as aggregations
  }

  // Automatically update periods once a day
  public function maybeUpdatePeriods() {
    $this->setClient($this->elasticEnv->periodHost);
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

  /**
   * Get total records count in main index
   */
  public function getTotalRecordsCount() {
    $searchParams = ['index'=>$this->elasticEnv->index];
    return $this->getClient()->count($searchParams)['count'];
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
      return $this->normalizer->aggsBucketsBeautifier($result, $this->aggregationsReqFilter);

    } catch (\Exception $e) {
      if (AppSettings::isLogging()) {
        AppSettings::debugLog($e->getMessage());
        AppSettings::debugLog(json_encode($searchParams, JSON_UNESCAPED_SLASHES));
      }
      return [
        'ERROR' => [
          'code' => $e->getCode(),
          'message'=> $e->getMessage()
        ]
      ];
    }
  }
}
