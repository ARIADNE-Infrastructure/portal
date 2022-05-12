<?php

namespace Elastic;

use Elastic\QuerySettings;
use Elastic\QueryPeriod;
use Elastic\Timeline;
use Elastic\Utils;
use Elastic\GeoUtils;
use Elastic\DataNormalizer;
use Elasticsearch\ClientBuilder;
use \geoPHP\geoPHP;

class Query {
  private $settings;
  private $logger;
  private $normalizer;
  private $client;
  private $elasticEnv;
  private $aggregationsReqFilter = []; // Aggregation filters from uri

  public function __construct($settings, $logger) {
    $this->settings = $settings;
    $this->logger = $logger;
    $this->normalizer = new DataNormalizer($settings);

    // Set current Elastic environment - prod|dev according to environment->elasticsearchEnv from settings.json
    $this->elasticEnv = $this->settings->elasticsearchEnv->{$this->settings->environment->elasticsearchEnv};

    // Setup Elastic client
    // $this->client = ClientBuilder::create()
    //   ->setHosts([$this->elasticEnv->host])
    //   ->build();
  }

  public function setClient($clientHost) {
    // Setup Elastic client
    $this->client = ClientBuilder::create()
    ->setHosts([$clientHost])
    ->build();
  }

  /**
   * Search Elastic db with given URI parameters
   */
  public function search() {

    $query = $this->getCurrentQuery();

    if (isset($_GET['mapq'])) {
      // This is the Map requesting data. Push map specific query attributes to main
      $query = $this->getMapQuery($query);
      //$query['size'] = $this->getMapResultSize($query);

      // When size is smaler than threshhold, geogrid is considered part of the result since we need to render heatmap
      if( intval($query['size']) <= $this->settings->environment->mapMarkerThreshhold ) {
        $query['aggregations']['geogrid'] = QuerySettings::getSearchAggregations()['geogrid'];
      }
    }

    $result = $this->elasticDoSearch($query);
    $r = $this->resultToFrontend($result);
    return $r;

  }

  /**
   * Build main elastic consumable query from/with GET params
   * Notice, this is the main body query, aggregations excluded!
   */
  private function getCurrentQuery () {

    $query['size'] = 10;
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
      $validFields = QuerySettings::getValidSearchableFields($_GET['q']);
      if($searchInField && array_key_exists($searchInField, $validFields) ) {
        $innerQuery['bool']['must'][] = $validFields[$searchInField]['query'];
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

    // Bounding box string has the following order:
    // 'topLeft.lat, topLeft.lon, bottomRight.lat, bottomRight.lon'
    if (!empty($_GET['bbox'])) {

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

      $mainQuery['query']['bool']['filter'][] = [
        'bool' => [
          'should' => $boundingBoxFilters
        ]
      ];

    }

    // Viewport
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
              array( 'exists' => ['field'=> 'spatial.geopoint']),
              array( 'exists' => ['field'=> 'spatial.polygon']),
              array( 'exists' => ['field'=> 'spatial.boundingbox'])
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
   * @return array Mini map data
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
      $query['aggregations']['geogrid'] = QuerySettings::getSearchAggregations()['geogrid'];
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
    if (!Utils::isValidId($recordId)) {
      exit;
    }

    $searchParams = [
      'id' => $recordId,
      'index' => $this->elasticEnv->index,
    ];

    $record = $this->elasticDoGet($searchParams);

    $record = $record['_source'];
    $record['id'] = $recordId;

    // TODO: Reduce roundtrips to Elastic!
    // Create one query for all of these.
    $record['similar'] = $this->getThematicallySimilarItems($record, $recordId);
    $record['nearby'] = $this->getNearbySpatialResources($record);
    $record['collection'] = $this->getCollectionItems($record, $recordId);
    $record['partOf'] = $this->getItemsPartOf($record, $recordId);
    $record['isAboutResource'] = $this->getIsAboutResources($record);

    $record = $this->normalizer->splitLanguages($record);

    return $record;
  }

  /**
   * Get all records from Elastic db
   */
  public function getAllRecords () {
    $searchParams = [];
    $searchParams['size'] = $this->getSize();
    $searchParams['from'] = $this->getFrom();

    $sort = $this->getSort();
    if ($sort) {
      $searchParams['sort'] = $sort;
    }
    return $this->elasticDoSearch($searchParams)['hits'];

  }

  /**
   * Gets autocomplete values
   */
  public function autocomplete () {
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
                  ['prefix' => ['prefLabels.label' => Utils::escapeLuceneValue($q)]]
                ],
              ],
            ],
          ],
        ],
      ];

      $search = $this->elasticDoSearch($query, $this->elasticEnv->subject_index);

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

    $q = trim($_GET['filterQuery'] ?? '');
    $filterName = trim($_GET['filterName'] ?? '');
    $query = '';

    $currentQuery = $this->getCurrentQuery()['query'];

    if ($q && $filterName) {

      switch (strtolower($filterName)) {
        case 'contributor':
          $query = AutocompleteFilterQuery::contributor($q,$currentQuery);
          break;
        case 'nativesubject':
          $query = AutocompleteFilterQuery::nativeSubject($q,$currentQuery);
          break;
        case 'ariadnesubject':
          $query = AutocompleteFilterQuery::ariadneSubject($q,$currentQuery);
          break;
        case 'derivedsubject':
          $query = AutocompleteFilterQuery::derivedSubject($q,$currentQuery);
          break;
        case 'publisher':
          $query = AutocompleteFilterQuery::publisher($q,$currentQuery);
          break;
        case 'temporal':
          $query = AutocompleteFilterQuery::temporal($q,$currentQuery);
          return $this->elasticDoSearch($query, $this->elasticEnv->index)['aggregations']['temporal_agg'];
          //return $this->elasticDoSearch($query, $this->elasticEnv->index)['aggregations'];

      }
      return $this->elasticDoSearch($query, $this->elasticEnv->index)['aggregations'];
      //return $this->elasticDoSearch($query, $this->elasticEnv->index)['aggregations']['filtered_agg'];

    }

    return null;

  }


  /**
   * Frontend wants data in a specifik form.
   *
   * @param array Result form ES to be formated
   * @return array Array formated according to frontend specs
   */
  private function resultToFrontend(&$result) {
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
        $from = ($from - 1) * 10;
      }
    }
    return $from;
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
                  'should' => [
                    $spatialMatches
                  ]
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
      if (is_string($part)) {
        $part = explode('/', $part);
        $part = end($part);
      }
      if (Utils::isValidId($part)) {
        $parts[] = ['match' => ['_id' => $part]];
      }
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
  private function getCollectionItems ($record, $recordId) {
    if (empty($record['resourceType']) || $record['resourceType'] !== 'collection') {
      return [];
    }

    $params = [
      '_source' => ['title'],
      'size' => 7,
      'query' => [
        'match' => [
          'isPartOf' => $recordId
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
    if (!Utils::isValidId($id)) {
      exit;
    }

    $subject = $this->elasticDoGet([
      'id' => $id,
      'index' => $this->elasticEnv->subject_index,
    ]);

    $subject = $subject['_source'];
    $subject['id'] = $id;
    $subject['connectedTotal'] = $this->getTotalConnectedSubjects($id);
    $subject['subSubjects'] = $this->getSubSubjects($id);

    return $subject;
  }

  /**
   * Returns subjects total amount of connected subjects
   */
  public function getTotalConnectedSubjects ($id) {
    $params = [
      'size' => 0,
      'query' => [
        'bool' => [
          'filter' => [
            'term' => [
              'derivedSubject.id' => $id,
            ],
          ],
        ],
      ],
    ];

    return $this->elasticDoSearch($params)['hits']['total']['value'] ?? 0;
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

    $subs = $this->elasticDoSearch($params, $this->elasticEnv->subject_index)['hits']['hits'] ?? [];
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


  public function getNormalizer() {
    return $this->normalizer;
  }

  public function getPeriodsCountryAggregationData() {
    $qp = new QueryPeriod();
    $result = $this->elasticDoSearch($qp->getCountriesAggQuery(), $this->elasticEnv->periodIndex);
    return $this->resultToFrontend($result);
  }

  public function getPeriodsForCountry() {
    $countryLabel = trim($_GET['country'] ?? '');
    $qp = new QueryPeriod();
    $result = $this->elasticDoSearch($qp->getPeriodsForCountryQuery($countryLabel), $this->elasticEnv->periodIndex);
    return $this->resultToFrontend($result);
  }




  /**
   * Get from Elastic host db
   */
  private function elasticDoGet ($searchParams) {
    try {
      $result = $this->client->get($searchParams);
      $this->logger->debug('Request URI: '. $_SERVER['REQUEST_URI']);
      $this->logger->debug(
        debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] .
        ' - ' . json_encode($searchParams, JSON_UNESCAPED_SLASHES)
      );

      return $result;

    } catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      $this->logger->debug(json_encode($searchParams, JSON_UNESCAPED_SLASHES));
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
      $result = $this->client->search($params);
      $this->logger->debug('Request URI: '. $_SERVER['REQUEST_URI']);
      $this->logger->debug(
        debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] .
        ' - ' . json_encode($searchParams, JSON_UNESCAPED_SLASHES)
        //' - ES Index: ' . $params['index']
      );
      //$this->logger->debug($params['index'] . ' - ' . json_encode($searchParams, JSON_UNESCAPED_SLASHES));

      $beautifiedResult = $this->normalizer->aggsBucketsBeautifier($result, $this->aggregationsReqFilter);
      return $beautifiedResult;

    } catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      $this->logger->debug(json_encode($searchParams, JSON_UNESCAPED_SLASHES));
      exit;
    }
  }
}
