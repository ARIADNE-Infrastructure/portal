<?php

namespace Elastic;
use Elastic\QuerySettings;
use Elastic\Timeline;
use Elastic\Utils;
use Elastic\AppSettings;
use Elasticsearch\ClientBuilder;
use Application\AppLogger;


class Query {
  private static $q = null;
  private $settings;
  private $appLogger = null;
  private $client;
  private $elasticEnv;
  private $debugMode = false;
  private $aggregationsReqFilter = []; // Aggregation filters from uri

  public function __construct() {

    // Get settings
    $this->settings = (new AppSettings())->getSettings();

    // Logger
    if( $this->settings->environment->debugLog ) {
      $this->appLogger = new AppLogger();
      $this->debugMode = true;
    }

    // Set current Elastic environment - prod|dev according to environment->elasticsearchEnv from settings.json
    $this->elasticEnv = $this->settings->elasticsearchEnv->{$this->settings->environment->elasticsearchEnv};

    // Setup Elastic client
    $this->client = ClientBuilder::create()
      ->setHosts([$this->elasticEnv->host])
      ->build();
  }

  /**
   * Gets singleton instance
   */
  public static function instance () {
    if (!self::$q) {
      self::$q = new Query();
    }
    return self::$q;
  }

  /**
   * Gets amounts of posts to return, default 10
   */
  public function getSize () {
    $size = intval($_GET['size'] ?? 10);
    if (is_int($size) && $size > 0 && $size <= 500) {
      return $size;
    }
    return 10;
  }

  /**
   * Get single record from Elastic db
   */
  public function getRecord ($recordId, $index) {
    if (!Utils::isValidId($recordId)) {
      exit;
    }

    $searchParams = [
      'id' => $recordId,
      'index' => $this->elasticEnv->index,
    ];

    $record = $this->elasticDoGet($searchParams)['_source'];

    $record['id'] = $recordId;
    $record['similar'] = $this->getThematicallySimilarItems($record, $recordId);
    $record['nearby'] = $this->getNearbySpatialItems($record);
    $record['collection'] = $this->getCollectionItems($record, $recordId);
    $record['partof'] = $this->getItemsPartOf($record, $recordId);

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
   * Word cloud
   */
  public function getWordCloudData () {
    $query = [
      'size' => 0,
      'aggregations' => [
        'derivedSubject' => [
          'terms' => [
            'field' => 'derivedSubject.prefLabel.raw',
            'size' => 150
          ]
        ]
      ],
    ];
    return $this->elasticDoSearch($query)['aggregations']['derivedSubject']['buckets'] ?? null;
  }

  /**
   * Gets autocomplete vaules
   */
  public function autocomplete () {
    $q = trim($_GET['q'] ?? '');

    if ($q) {
      $query = [
        '_source' => ['prefLabel', 'prefLabels'],
        'size' => 10,
        'query' => [
          'nested' => [
            'path' => 'prefLabels',
            'query' => [
              'bool' => [
                'must' => [
                  ['prefix' => ['prefLabels.label' => Utils::escapeLuceneValue($q)]],
                  ['match' => ['prefLabels.lang' => 'en']]
                ]
              ]
            ]
          ]
        ]
      ];

      $hits = $this->elasticDoSearch($query, $this->elasticEnv->subject_index)['hits']['hits'];
      $result = [];

      foreach ($hits as $hit) {
        $label = $hit['_source']['prefLabel'];
        $variants = [];
        if (!empty($hit['_source']['prefLabels'])) {
          foreach ($hit['_source']['prefLabels'] as $variant) {
            if ($variant['lang'] === 'en' && $variant['label'] !== $label) {
              $variants[] = $variant;
              if (count($variants) > 4) {
                break;
              }
            }
          }
        }
        $result[] = [
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
   * Search Elastic db with given URI parameters
   */
  public function search () {
    $result = $this->elasticDoSearch($this->getCurrentQuery());
    $hits = [];
    foreach ($result['hits']['hits'] as $hit) {
      $hits[] = [
        'id' => $hit['_id'],
        'data' => $hit['_source']
      ];
    }

    return [
      'total' => $result['hits']['total'],
      'hits' => $hits,
      'aggregations' => $result['aggregations'],
    ];
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
   * Build Elastic-API consumable query
   */
  private function getCurrentQuery () {
    $query = ['aggregations' => QuerySettings::getSearchAggregations()];
    $query['size'] = $this->getSize();

    // Geogrid Hash precision
    $ghp = isset($_GET['ghp']) ? $_GET['ghp'] : 4;
    if($ghp>12) {
      $ghp = 12; // Max allowed Elastic precision 
    }

    $query['aggregations']['geogrid'] = ['geohash_grid' => [
      'field' => 'spatial.location', 'precision' => intval($ghp), 'size' => 10000
    ]];

    // add timespan bucket aggregation
    $range = null;
    if (!empty($_GET['range'])) {
      $range = explode(",", $_GET['range']);
    }

    $query['from'] = $this->getFrom();

    $sort = $this->getSort();
    if ($sort) {
      $query['sort'] = $sort;
    }

    $innerQuery = null;

    if (isset($_GET['q'])) {
      $_GET['q'] = trim($_GET['q']);
      if (empty($_GET['q'])) {
        $_GET['q'] = '*';
      }

      $field_groups = QuerySettings::getSearchFieldGroups();

      if(isset($_GET['fields']) && !empty($field_groups[$_GET['fields']])){
          foreach ($field_groups[$_GET['fields']] as $field){
              if($_GET['fields'] === 'time'){
                  $innerQuery['bool']['should'][] = ['nested' => [
                      'path' => 'temporal',
                      'query' => [
                          'bool' => [
                              'must' => [
                                  'match' => [
                                      $field =>  Utils::escapeLuceneValue($_GET['q'])
                                  ]
                              ]
                          ]
                      ]
                  ]];
              }else{
                  $innerQuery['bool']['should'][] = ['match' => [$field => Utils::escapeLuceneValue($_GET['q'])]];
              }
          }
      } else {

          if($_GET['q'] == '*') {
            $innerQuery = array('match_all'=> new \stdClass() );
          } else {
            $innerQuery =  ['multi_match' => ['query' => Utils::escapeLuceneValue($_GET['q']) ]];
          }

      }
    } else {
        //$innerQuery = ['match_all' => []];
        $innerQuery = array('match_all'=> new \stdClass());
    }

    $filters = [];

    if (isset($_GET['subjectUri'])) {
        $filters[] = [
            'term' => [ 'aatSubjects.id' => intval($_GET['subjectUri'])]
        ];
    }

    foreach ($query['aggregations'] as $key => $aggregation) {
        if (!empty($_GET[$key])) {
          $values = explode('|', $_GET[$key]);

            if ($key != 'temporal') {
              $field = $aggregation['terms']['field'];
            } else {
              $field = $aggregation['aggs']['temporal']['terms']['field'];
            }

            foreach ($values as $value) {
                $fieldQuery = [];
                $fieldQuery[$field] = Utils::escapeLuceneValue($value, false);
                // Add value to beautify result
                $this->aggregationsReqFilter[$key][] = Utils::escapeLuceneValue($value, false);
                if ($key != 'temporal'){
                  $filters[] = ['term' => $fieldQuery];
                } else {
                  $filters[] = ['nested' => [
                          'path' => 'temporal',
                          'query' => [
                              'bool'=> [
                                  'must' => ['term' => $fieldQuery]
                              ]
                          ]
                      ]
                  ];
                }
            }
        }
    }

    if (!empty($_GET['range'])) {
        $range = explode(',', $_GET['range']);
        if (sizeof($range) > 1) {
            $filters[] =  [
                'nested' => [
                    'path' => 'temporal',
                    'query' => Timeline::buildRangeQuery(
                        intval($range[0]),
                        intval($range[sizeof($range)-1])
                    )
                ]
            ];
        }
    }

    // TODO: refactor so that ES service takes care of bbox parsing
    if (!empty($_GET['bbox'])) {
        $bbox = explode(',', $_GET['bbox']);
        $filters[] = [
            'geo_bounding_box' => [
                'spatial.location' => [
                    'top_left' => [
                        'lat' => floatval($bbox[3]),
                        'lon' => floatval($bbox[0])
                    ],
                    'bottom_right' => [
                        'lat' => floatval($bbox[1]),
                        'lon' => floatval($bbox[2])
                    ]
                ]
            ]
        ];
    }

    foreach ($filters as $filter) {
      $query['query']['bool']['filter'][] = $filter;
    }

    $query['query']['bool']['must'] = $innerQuery;

    // RangeBuckets is not needed for Map search
    if (empty($_GET['mapq'])) {
      $query['aggregations']['range_buckets'] = Timeline::prepareRangeBucketsAggregation($range);
    }

    // Map search:
    // 1. Viewport
    // 2. Fetch only needed fields for each resource 
    // 3. Fetch only resources with Spatial Data
    if (!empty($_GET['mapq'])) {

      // Viewport is needed only for Map search
      $query['aggregations']['viewport'] = [
          'geo_bounds' => [
            'field' => 'spatial.location',
            'wrap_longitude' => true,
          ]
      ];
      // Fetch only fields needed for map. 
      $query['_source'] = ['title','description','resourceType','publisher','archaeologicalResourceType','spatial'];
      // Fetch only resources with spatial data
      $query['query']['bool']['filter'][] = ['exists' => ['field' => 'spatial.location']];

    }

    return $query;

  }

  /**
   * Gets a records nearby spatial items
   */
  private function getNearbySpatialItems ($record) {
    $location = null;

    if (!empty($record['spatial'])) {
      foreach ($record['spatial'] as $item) {
        if (!empty($item['location']) && !empty($item['location']['lat']) && !empty($item['location']['lon'])) {
          $location = $item;
          break;
        }
      }
    }

    if (!$location) {
      return null;
    }

    $params = [
      '_source' => ['spatial'],
      'query' => [
        'bool' => [
          'filter' => [
            'geo_distance' => [
              'distance' => '50km',
              'spatial.location' => [
                'lat' => $location['location']['lat'],
                'lon' => $location['location']['lon']
              ]
            ]
          ]
        ]
      ],
      'sort' => [
        '_geo_distance' => [
          'spatial.location' => [
            'lat' => $location['location']['lat'],
            'lon' => $location['location']['lon']
          ],
          'order' => 'asc',
          'unit' => 'km',
          'distance_type' => 'plane'
        ]
      ]
    ];

    $locations = $this->elasticDoSearch($params)['hits']['hits'];
    $ret = [];

    if (!empty($locations)) {
      foreach ($locations as $loc) {
        $loc = $loc['_source']['spatial'][0] ?? null;
        if ($loc && !Utils::isLocationDoublet($location, $loc)) {
          $doublet = false;
          foreach ($ret as $r) {
            if (Utils::isLocationDoublet($loc, $r)) {
              $doublet = true;
              break;
            }
          }
          if (!$doublet) {
            $ret[] = $loc;
          }
        }
      }
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
        $matches[] = [
          'match' => [
            'title' => Utils::escapeLuceneValue($record['title'])
          ]
        ];
      }
    } else if ($type === 'location') {
      if (!empty($record['spatial'])) {
        foreach ($record['spatial'] as $spatial) {
          if (!empty($spatial['placeName'])) {
            $matches[] = [
              'match' => [
                'spatial.placeName' => str_replace(["\r", "\n", "\t", "\v"], '', $spatial['placeName']),
              ],
            ];
          }
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
        foreach ($record['temporal'] as $temporal) {
          if (!empty($temporal['periodName'])) {
            $matches[] = [
              'match' => [
                'temporal.periodName' => str_replace(["\r", "\n", "\t", "\v"], '', $temporal['periodName']),
              ],
            ];
          }
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
        foreach ($record['temporal'] as $temporal) {
          if (!empty($temporal['periodName'])) {
            $matches[] = [
              'match' => [
                'temporal.periodName' => str_replace(["\r", "\n", "\t", "\v"], '', $temporal['periodName']),
              ],
            ];
          }
        }
      }
    }

    if (empty($matches)) {
      return null;
    }

    $params = [
      '_source' => ['title', 'archaeologicalResourceType'],
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
      $ret[] = [
        'id' => $res['_id'],
        'type' => $res['_source']['archaeologicalResourceType'] ?? null,
        'title' => $res['_source']['title'] ?? null,
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
    $ret = [];

    foreach ($result as $hit) {
      $ret[] = [
        'id' => $hit['_id'],
        'title' => $hit['_source']['title'] ?? null,
      ];
    }
    return $ret;
  }

  /**
   * Gets a records collection items, and total value
   */
  private function getCollectionItems ($record, $recordId) {
    if (empty($record['resourceType']) || $record['resourceType'] !== 'collection') {
      return null;
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
        $hits[] = [
          'id' => $hit['_id'],
          'title' => $hit['_source']['title'] ?? null,
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
              'aatSubjects.id' => $id,
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

  /**
   * Get from Elastic host db
   */
  private function elasticDoGet ($searchParams) {

    try {
      $result = $this->client->get($searchParams);
      if($this->debugMode) {
        $this->appLogger->info( json_encode( $searchParams, JSON_UNESCAPED_SLASHES ) );
      }
      return $result;

    } catch (\Exception $e) {

      if($this->debugMode) {
        $this->appLogger->log->error('ERROR', ['Exception' => $e->getMessage()]);
        $this->appLogger->info( json_encode( $searchParams, JSON_UNESCAPED_SLASHES ) );
      }
      exit;
    }
  }


  /**
   * Search Elastic host db
   */
  private function elasticDoSearch ($searchParams, $index = null) {
    $searchParams['track_total_hits'] = true;

    $params = [
      'index' => $index ?: $this->elasticEnv->index,
      'body'  => $searchParams,
    ];

    try {
      $result = $this->client->search($params);

      if($this->debugMode) {
        $this->appLogger->info( json_encode( $searchParams, JSON_UNESCAPED_SLASHES ) );
      }

      $beautifiedResult = $this->aggsBucketsBeautifier($result);
      return $beautifiedResult;

    } catch (\Exception $e) {
      if($this->debugMode) {
        $this->appLogger->info( json_encode( $searchParams, JSON_UNESCAPED_SLASHES ) );
        $this->appLogger->log->error('ERROR', ['Exception' => $e->getMessage()]);
      }
      exit;
    }

  }

  /**
   * Beauitify result aggregations that have zero buckets.
   *
   * In cases where aggs has buckets[0] as result from Elastic we push
   * {"key":"<aggregation_key_value>","doc_count":0} instead of returning a blank array
   *
   * This is manipulating Elastic resultsets and exist only so that frontend can filter and
   * render chosen filters correctlly.
   */
  private function aggsBucketsBeautifier($result) {

    if( isset($result['aggregations']) ) {
      foreach($result['aggregations'] as $aggKey=>$aggValue) {
        if(key_exists($aggKey, $this->aggregationsReqFilter)) {
          if( empty($aggValue['buckets']) ) {
            foreach($this->aggregationsReqFilter[$aggKey] as $key=>$value) {
              // This means that buckets is an empty array.
              // Put values into it to simplify client side rendering of chosen filters/aggs by user.
              $result['aggregations'][$aggKey]['buckets'][] = array('key'=>$value, 'doc_count'=>0);
            }
          }
        }
      }
    }
    return $result;
  }

}

