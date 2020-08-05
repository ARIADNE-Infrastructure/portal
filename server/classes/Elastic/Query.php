<?php

namespace Elastic;
use Elastic\QuerySettings;
use Elastic\Timeline;
use Elastic\Utils;
use Elastic\AppSettings;
use Elasticsearch\ClientBuilder;
use Application\AppLogger;


class Query {

  private $settings;
  private $appLogger;
  private $client;

  public function __construct() {
    $this->settings = (new AppSettings())->getSettings();
    $this->appLogger = new AppLogger();

    $this->client = ClientBuilder::create()
      ->setHosts([$this->settings->elasticsearch->host])
      ->build();
  }

  /**
   * Get singel record from Elastic db
   */
  public function getRecord ($recordId, $index) {
    $searchParams = [
      'id' => intval($recordId),
      'index' => $this->settings->elasticsearch->index,
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
    $returnSize = 10;
    if($returnSize!== '') {
      $searchParams['size'] = $returnSize;
      $searchParams['from'] = $this->getFrom();

      $sort = $this->getSort();
      if ($sort) {
        $searchParams['sort'] = $sort;
      }
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
        'size' => 6,
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

      $hits = $this->elasticDoSearch($query, $this->settings->elasticsearch->subject_index)['hits']['hits'];
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

    // Elastic returns that there can be more than 10k results, but crashes when navigating further than that
    // So limit results to 10k
    if (isset($result['hits']['total']['value']) && $result['hits']['total']['value'] > 10000) {
      $result['hits']['total']['value'] = 10000;
      $result['hits']['total']['relation'] = 'lg';
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

    $ghp = isset($_GET['ghp']) ? $_GET['ghp'] : 3;

    $query['aggregations']['geogrid'] = ['geohash_grid' => [
        'field' => 'spatial.location', 'precision' => intval($ghp), 'size' => 20000
    ]];

    // add timespan bucket aggregation
    $range = null;
    if(isset($_GET['range'])) {
      $range = explode(",", $_GET['range']);
    }

    $query['from'] = $this->getFrom();
    $query['aggregations']['range_buckets'] = Timeline::prepareRangeBucketsAggregation($range);

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
              $innerQuery = array('match_all'=> (object) array());
            } else {
              $innerQuery =  ['multi_match' => ['query' => Utils::escapeLuceneValue($_GET['q']) ]];
            }

        }
    } else {
        //$innerQuery = ['match_all' => []];
        $innerQuery = array('match_all'=> (object) array());

    }

    $filters = [];

    if (isset($_GET['subjectUri'])) {
        $filters[] = [
            'term' => [ 'aatSubjects.id' => intval($_GET['subjectUri'])]
        ];
    }

    foreach ($query['aggregations'] as $key => $aggregation) {

        //if (Request::has($key)) {
          if (isset($_GET[$key])) {

            $values = Utils::getArgumentValues($key);

            if ($key != 'temporal') {
              $field = $aggregation['terms']['field'];
            } else {
              $field = $aggregation['aggs']['temporal']['terms']['field'];
            }

            foreach ($values as $value) {
                $fieldQuery = [];
                $fieldQuery[$field] = $value;
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

    if (isset($_GET['range'])) {
        $range = explode(',', $_GET['range']);
        if (sizeof($range) > 1) {
            $filters[] =  [
                'nested' => [
                    'path' => 'temporal',
                    'query' => Timeline::buildRangeQuery(
                        $range[0],
                        $range[sizeof($range)-1]
                    )
                ]
            ];
        }
    }

    // TODO: refactor so that ES service takes care of bbox parsing
    if (isset($_GET['bbox'])) {
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
          $location = $item['location'];
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
                'lat' => $location['lat'],
                'lon' => $location['lon']
              ]
            ]
          ]
        ]
      ],
      'sort' => [
        '_geo_distance' => [
          'spatial.location' => [
            'lat' => $location['lat'],
            'lon' => $location['lon']
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
        if ($loc && ($loc['location']['lat'] !== $location['lat'] || $loc['location']['lon'] !== $location['lon'])) {
          $ret[] = $loc;
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

    if (!empty($record['nativeSubject'])) {
      foreach ($record['nativeSubject'] as $subject) {
        if (!empty($subject['prefLabel'])) {
          $matches[] = [
            'match' => [
              'nativeSubject.prefLabel' => $subject['prefLabel']
            ]
          ];
        }
      }
    }
    if (!empty($record['temporal'])) {
      foreach ($record['temporal'] as $temporal) {
        if (!empty($temporal['periodName'])) {
          $matches[] = [
            'match' => [
              'temporal.periodName' => str_replace(["\r", "\n", "\t", "\v"], '', $temporal['periodName'])
            ]
          ];
        }
      }
    }

    if (empty($matches)) {
      return null;
    }

    $params = [
      '_source' => ['title'],
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
      if (is_numeric($part)) {
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
   * Get from Elastic host db
   */
  private function elasticDoGet ($searchParams) {

    try {
      $result = $this->client->get($searchParams);
      $last = $this->client->transport->getLastConnection()->getLastRequestInfo();
      $body = $last['request']['body'];
      $this->appLogger->log->info('Get', ['Query' => json_decode($body)]);
      return $result;

    } catch (\Exception $e) {
      $this->appLogger->log->error('ERROR', ['Exception' => $e->getMessage()]);
      exit;
    }
  }


  /**
   * Search Elastic host db
   */
  private function elasticDoSearch ($searchParams, $index = null) {
    $searchParams['track_total_hits'] = true;
    $params = [
      'index' => $index ?: $this->settings->elasticsearch->index,
      'body' => $searchParams
    ];

    try {
      $result = $this->client->search($params);
      $last = $this->client->transport->getLastConnection()->getLastRequestInfo();
      $body = $last['request']['body'];
      $this->appLogger->log->info('Search', ['Query' => json_decode($body)]);
      return $result;

    } catch (\Exception $e) {
      $this->appLogger->log->error('ERROR', ['Exception' => $e->getMessage()]);
      exit;
    }

  }

}
