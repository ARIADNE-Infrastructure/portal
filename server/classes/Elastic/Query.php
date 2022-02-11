<?php

namespace Elastic;

use Elastic\QuerySettings;
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
    $this->client = ClientBuilder::create()
      ->setHosts([$this->elasticEnv->host])
      ->build();
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
    //$record['nearby'] = $this->getNearbySpatialItems($record);
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
    $q = trim($_GET['q'] ?? '');

    if (!$q) {
      return null;
    }

    if ($fields !== 'aatSubjects') {

      $innerQuery =  null;

      if($fields == 'all') {
        $innerQuery = [
          'multi_match' => [
            'query' => Utils::escapeLuceneValue($q),
            'type' => 'bool_prefix'
            ]
        ];

      } else {

        if($fields == 'title') {
          $innerQuery = [
            'match_bool_prefix' => [
              'title.text' => Utils::escapeLuceneValue($q)
            ]
          ];

        } else if($fields == 'location') {

          $innerQuery = [
            'nested' => [
              'path' => 'spatial',
              'query' => [
                'match_bool_prefix' => [
                  'spatial.placeName' => Utils::escapeLuceneValue($q)
                ]
              ]
            ]
          ];

        } else if( $fields == 'time' ) {
          $innerQuery = [
            'nested' => [
              'path' => 'temporal',
              'query' => [
                'match_bool_prefix' => [
                  'temporal.periodName' => Utils::escapeLuceneValue($q)
                ]
              ]
            ]
          ];

        }

      }

      $query = [
        '_source' => ['title'],
        'query' => $innerQuery,
        'highlight' => [
          'fields' => [
            '*' => new \stdClass()
          ]
        ]
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
                ]
              ]
            ]
          ]
        ]
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
   * Search Elastic db with given URI parameters
   */
  public function search () {
    $result = $this->elasticDoSearch($this->getCurrentQuery());
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

    if (isset($_GET['q'])) {
      $_GET['q'] = trim($_GET['q']);
    }

    // Geogrid Hash precision
    $ghp = isset($_GET['ghp']) ? intval($_GET['ghp']) : 4;
    if ($ghp > 12) {
      $ghp = 12; // Max allowed Elastic precision
    }

    $query['aggregations']['geogrid'] = [
      'nested' => [
        'path' => 'spatial'
      ],
      'aggs' => [
        'grids' => [
          'geohash_grid' => [
            'field' => 'spatial.geopoint',
            'precision' => intval($ghp),
            'size' => 10000
          ]
        ]
      ]
    ];

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

    if (!empty($_GET['q']) ) {

      // Get mapping between URI params for 'fields' and actuall ES mapping names
      $field_groups = QuerySettings::getSearchFieldGroups();

      if(isset($_GET['fields']) && !empty($field_groups[$_GET['fields']]) ) {
          foreach ($field_groups[$_GET['fields']] as $field){

              if($_GET['fields'] === 'time') {

                $innerQuery['bool']['should'][] = ['nested' => [
                  'path' => 'temporal',
                  'query' => [
                    'bool' => [
                      'must' => [
                        'match_phrase_prefix' => [
                          $field =>  Utils::escapeLuceneValue($_GET['q'])
                        ]
                      ]
                    ]
                  ]
                ]];

              } else if ($_GET['fields'] === 'location') {

                $innerQuery['bool']['should'][] = ['nested' => [
                  'path' => 'spatial',
                  'query' => [
                    'bool' => [
                      'must' => [
                        'match_phrase_prefix' => [
                          $field =>  Utils::escapeLuceneValue($_GET['q'])
                        ]
                      ]
                    ]
                  ]
                ]];

              } else if( ($_GET['fields']==='title') || ($_GET['fields']==='aatSubjects') ) {
                $innerQuery['bool']['should'][] = ['match_phrase_prefix' => [$field => Utils::escapeLuceneValue($_GET['q'])]];

              } else {

                $innerQuery['bool']['should'][] = ['match' => [$field => Utils::escapeLuceneValue($_GET['q'])]];

              }

          }
      } else {
          if (empty($_GET['q'])) {
            $innerQuery = array('match_all'=> new \stdClass() );
          } else {
            $innerQuery =  ['multi_match' => ['query' => Utils::escapeLuceneValue($_GET['q']) ]];
          }
      }

    } else {
        $innerQuery = array('match_all'=> new \stdClass());
    }

    $filters = [];

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

    // Handle searching for fields that are not present as aggregations and are more direct exact querys to keywords etc.
    // These querys are mostly triggered from the resource page.
    // URI looks like: /search?creator=exactCreatorName
    //
    if(!empty($_GET['creator'])) {
      $filters[] = [
        'term' => ['creator.name.raw' => $_GET['creator']]
      ];
    } else if(!empty($_GET['owner'])) {
      $filters[] = [
        'term' => ['owner.name.raw' => $_GET['owner']]
      ];
    } else if(!empty($_GET['responsible'])) {
      $filters[] = [
        'term' => ['responsible.name.raw' => $_GET['responsible']]
      ];
    } else if(!empty($_GET['resourceType'])) {
      $filters[] = [
        'term' => ['resourceType' => $_GET['resourceType']]
      ];
    } else if(!empty($_GET['placeName'])) {
      $filters[] = [
        'nested' => [
          'path' => 'spatial',
          'query' => [
            'bool' => [
              'must' => [
                'match_phrase' => [
                  'spatial.placeName' => $_GET['placeName']
                ]
              ]
            ]
          ]
        ]
      ];
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

    // Incoming bounding box string has the following order:
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

      $filters[] = [
        'bool' => [
          'should' => $boundingBoxFilters
        ]

      ];

    }

    // derivedSubjectId
    if (!empty($_GET['derivedSubjectId'])) {
      $query['query']['bool']['must'][] = [
        'match' => [
          'derivedSubject.id' => $_GET['derivedSubjectId']
        ]
      ];
    }

    foreach ($filters as $filter) {
      $query['query']['bool']['filter'][] = $filter;
    }

    if((!empty($_GET['q'])) && ($_GET['fields'] ?? '' === 'all')) {
      //$query['query']['bool']['must']['bool']['should'] = QuerySettings::getValidSearchableFields( Utils::escapeLuceneValue($_GET['q']) );
      $query['query']['bool']['should'][] = QuerySettings::getValidSearchableFields( Utils::escapeLuceneValue($_GET['q']) );

    } else {
      $query['query']['bool']['must'][] = $innerQuery;
    }

    // Map search:
    // 1. Viewport
    // 2. Fetch only needed fields for each resource
    // 3. Fetch only resources with Spatial Data
    if (!empty($_GET['mapq'])) {
      // Viewport is needed only for Map search
      $query['aggregations']['viewport'] = [
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

      // Fetch only fields needed for map.
      $query['_source'] = ['title','description','resourceType','publisher','ariadneSubject','spatial'];

      // Fetch only resources with spatial data

      $query['query']['bool']['must'][] = [
        'nested' => [
          'path' => 'spatial',
          'query' => [
            'bool' => [
              'should' => [
                array( 'exists' => ['field'=> 'spatial.geopoint']),
                array( 'exists' => ['field'=> 'spatial.polygon']),
                array( 'exists' => ['field'=> 'spatial.boundingbox'])
              ],
              'must' => [
                'match_all' => new \stdClass()
              ]
            ]
          ]
        ]
      ];
    }
    return $query;
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
   * Gets a records nearby spatial items
   *
   * GeoPHP
   * https://packagist.org/packages/funiq/geophp
   */
  private function getNearbySpatialItems ($record) {
    $latLon = null;

    if (!empty($record['spatial'])) {
      foreach ($record['spatial'] as $item) {
        if (!empty($item['geopoint']) && !empty($item['geopoint']['lat']) && !empty($item['geopoint']['lon'])) {
          $latLon['lat'] = $item['geopoint']['lat'];
          $latLon['lon'] = $item['geopoint']['lon'];
          break;
        } else if(!empty($item['polygon']) || !empty($item['boundingbox']) ) {
          $geoShape = !empty($item['polygon']) ? $item['polygon'] : $item['boundingbox'];
          $geoShape = geoPHP::load( $geoShape ,'wkt');
          $latLon['lat'] = $geoShape->getCentroid()->getY();
          $latLon['lon'] = $geoShape->getCentroid()->getX();
          break;
        }
      }
    }

    if (!$latLon) {
      return [];
    }

    // * * *
    // NOTICE!
    // Current Elastic version is 7.4 and doesn't support geo distance query for geo_shape property
    //
    // There fore we must construct own boundingbox and query geopoint, polygon and boudningbox props
    // with shape > envelope instead.
    //
    // TODO:
    // When ES goes higher than 7.11 (introduction of distance geo query) change this to geo_distance
    // https://www.elastic.co/guide/en/elasticsearch/reference/7.11/query-dsl-geo-distance-query.html
    // * * *

    $minLat = $latLon['lat'] - (0.009 * 10);
    $maxLat = $latLon['lat'] + (0.009 * 10);
    $minLon = $latLon['lon'] - (0.009 * 10);
    $maxLon = $latLon['lon'] + (0.009 * 10);

    $bbox['upperLeft'] = $minLon.','.$minLat;
    $bbox['lowerRight'] = $maxLon.','.$maxLat;

    $params = [
      '_source' => ['spatial','title'],
      'query' => [
        'bool' => [
          'filter' => [
            'nested' => [
              'path' => 'spatial',
              'query' => [
                'geo_distance' => [
                  'distance' => '50km',
                  'spatial.geopoint' => [
                    'lat' => $latLon['lat'],
                    'lon' => $latLon['lon']
                  ]
                ]
              ]
            ]
          ]
        ]
      ],
      'sort' => [
        '_geo_distance' => [
          'order' => 'asc',
          'unit' => 'km',
          'distance_type' => 'plane',
          'nested_path' => 'spatial',
          'spatial.geopoint' => [
            'lat' => $latLon['lat'],
            'lon' => $latLon['lon']
          ]
        ]
      ]
    ];

    $locations = $this->elasticDoSearch($params)['hits']['hits'];
    $nearbyGeopoints = [];

    if (!empty($locations)) {
      foreach ($locations as $loc) {
        $nLoc = $this->normalizer->splitLanguages($loc['_source']);
        if($loc['_id'] !== $record['id'] ) {
          foreach ($loc['_source']['spatial'] as $l) {
            if(isset($l['geopoint'])) {
              $l['title'] = $nLoc['title'];
              $l['id'] = $loc['_id'];
              $nearbyGeopoints[] = $l;
            }
          }
        }
      }
    }

    return $nearbyGeopoints;
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
                  'should' => [
                    $temporalMatches
                  ]
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
                  'should' => [
                    $temporalMatches
                  ]
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


  public function getNormalizer() {
    return $this->normalizer;
  }


  public function getTimelineData() {
    $range = null;
    if (!empty($_GET['range'])) {
      $range = explode(',', $_GET['range']);
    }

    $query = $this->getCurrentQuery();
    $query['size'] = 0;
    unset($query['from']);
    unset($query['sort']);
    unset($query['aggregations']);

    $query['aggregations']['range_buckets'] = Timeline::prepareRangeBucketsAggregation($range);
    $result = $this->elasticDoSearch($query)['aggregations']['range_buckets'];

    return $result;
  }

  /**
   * Get from Elastic host db
   */
  private function elasticDoGet ($searchParams) {
    try {
      $result = $this->client->get($searchParams);

      $this->logger->debug('Request URI: '. $_SERVER['REQUEST_URI']);
      $this->logger->debug('GET RECORD: ' . json_encode($searchParams, JSON_UNESCAPED_SLASHES));

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
      $this->logger->debug($params['index'] . ' - ' . json_encode($searchParams, JSON_UNESCAPED_SLASHES));

      $beautifiedResult = $this->normalizer->aggsBucketsBeautifier($result, $this->aggregationsReqFilter);
      return $beautifiedResult;

    } catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      $this->logger->debug(json_encode($searchParams, JSON_UNESCAPED_SLASHES));
      exit;
    }
  }
}
