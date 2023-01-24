<?php

namespace AAT;

use Application\AppSettings;
use Elasticsearch\ClientBuilder;
use Elastic\Query;



class AATDescendants {
  private $elasticEnv;
  private $tree;
  private $currentRecordURI;
  private $dataFolder;
  private $recordList = [];

  /**
   * Constructor
   */
  public function __construct() {
    $this->elasticEnv = AppSettings::getSettingsEnv();
    $this->dataFolder = dirname(__FILE__) . '/data/aat-desc';
  }


  /**
   * Main function.
   * Iterate all records from OpenSearch and fetch children
   * Note: this function can take over 1 hour
   */
  public function createTree() {
    if (!is_dir($this->dataFolder)) {
      die('No data folder exists');
    }

    clearstatcache();
    ini_set('max_execution_time', '10000');
    ini_set('memory_limit', '1024M');
    $stage = intval($_GET['stage'] ?? 0);

    if ($stage === 1) {
      // Get all from OpenSearch
      $this->recordList = $this->getTermsFromSource();

      // Iterate ecah record and find children records
      foreach ($this->recordList as $i=>$record) {
        $this->currentRecordURI = $record['_source']['uri'];
        $this->tree[$this->currentRecordURI] = [
          'id' => $record['_id'],
          'uri' => $record['_source']['uri'],
          'prefLabel' => $record['_source']['prefLabel'],
          'descendants' => []
        ];
        $this->getAllDescendants($this->currentRecordURI, $record['_source']['uri']);
        unset($record);
      }

      // Create and write results to file in JSON format.
      // Later to be used to add data to OpenSearch index for AAT descendants
      file_put_contents($this->dataFolder . '/term-descendants.json', json_encode($this->tree));
      echo 'Stage 1 done.';

    } else if ($stage === 2) {
      // Index resources with created termDescendants.json data
      $this->indexTermDescendants();
      echo 'Stage 2 done.';

    } else {
      echo 'Select stage: [1,2].';
    }
  }

  /**
   * Recursive function to drill and find given term descendants (children).
   */
  function getAllDescendants ($currentId, $parentUri) {
    foreach ($this->recordList as $recId=>$record) {
      foreach ($record['_source']['broader'] as $id=>$broader) {
        if ($broader['uri'] === $parentUri) {
          if (!in_array($record['_source']['uri'],$this->tree[$currentId]['descendants'])) {
            $this->tree[$currentId]['descendants'][] = [
              'uri' => $record['_source']['uri'],
              'prefLabel' => $record['_source']['prefLabel'],
            ];
          }
          $this->getAllDescendants($currentId, $record['_source']['uri']);
        }
      }
    }
  }

  /**
   * OpenSearch query to get all AAT records. Uses the scroll api (more than 10k posts)
   */
  private function getTermsFromSource() {
    $client = Query::instance()->getClient();
    $res = [];

    try {
      $firstRes = $client->search([
        'index' => $this->elasticEnv->subjectIndex,
        'scroll' => '5m',
        'body'  => [
          'size' => 10000,
          '_source' => ['broader', 'uri', 'prefLabel'],
          'track_total_hits' => true,
          'query' => [
            'match_all' => new \stdClass()
          ],
        ],
      ]);

      $scrollId = $firstRes['_scroll_id'];
      $res = $firstRes['hits']['hits'];

      while (true) {
        $scrollRes = $client->scroll(['scroll_id' => $scrollId, 'scroll' => '5m']);
        if (!empty($scrollRes['hits']['hits'])) {
          $res = array_merge($res, $scrollRes['hits']['hits']);
          $scrollId = $scrollRes['_scroll_id'];
        } else {
          break;
        }
      }

    } catch (\Exception $e) {
      AppSettings::debugLog($e->getMessage());
      die($e->getMessage());
    }

    return $res;
  }

  /**
   * OpenSearch query to get all AAT term descendants.
   */
  public function getDescendants($term) {
    // Ariadne resources derivedSubject.prefLabel keyword doesn't allways match aat-index prefLabel.
    // Get requested term ID (URI). to do an exact match between Ariadne resource and AAT-index.

    // get URI for gicen term from Ariande index.
    $query = [
      'size' => 1,
      'query' => [
        'term' => [
          'derivedSubject.prefLabel.raw' => $term
        ]
      ],
    ];

    $resource = Query::instance()->elasticDoSearch($query, $this->elasticEnv->index);

    // filter out the requested term and get URI
    $subj = $resource['hits']['hits'][0]['_source']['derivedSubject'] ?? null;
    if (!$subj) {
      return [];
    }

    $derivedSubject = array_filter($subj, function ($var) use ($term)  {
      return $var['prefLabel'] === $term;
    });

    $aatID = reset($derivedSubject)['id']; // get first id
    if($aatID) {
      $descendants = [];
      $aatID = substr($aatID, strrpos($aatID, '/') + 1);

      // get descendats with given uri
      $descendatsQuery = [
        'size' => 10000,
        'query' => [
          'term' => [
            'id' => $aatID
          ]
        ],
      ];

      $descentandsResult = Query::instance()->elasticDoSearch($descendatsQuery, $this->elasticEnv->aatTermDescendantsIndex);

      if(!empty($descentandsResult['hits']['hits'])) {
        // allways include requested term
        $descendants[] = [
          'uri' => $descentandsResult['hits']['hits'][0]['_source']['uri'],
          'prefLabel' => $descentandsResult['hits']['hits'][0]['_source']['prefLabel']
        ];

        if(count($descentandsResult['hits']['hits'][0]['_source']['descendants'])) {
          // add all descendants to current requested term
          // array_push( $descendants, array_values($descentandsResult['hits']['hits'][0]['_source']['descendants']));
          $descendants = array_merge( $descendants, $descentandsResult['hits']['hits'][0]['_source']['descendants']);

        }
        return $descendants;
      }
    }

    return [];
  }

  /**
   * Takes termDescendants.json file and indexes OpenSearch docs
   *
   */
  private function indexTermDescendants() {
    $terms = json_decode(file_get_contents($this->dataFolder . '/term-descendants.json'), true);
    if (empty($terms)) {
      die('No data file exists');
    }

    $client = ClientBuilder::create()->setHosts([$this->elasticEnv->host])->build();
    try {
      $client->indices()->delete(['index' => $this->elasticEnv->aatTermDescendantsIndex]);
    } catch (\Exception $ex) {
      AppSettings::debugLog($ex->getMessage());
    }

    try {
      $client->indices()->create([
        'index' => $this->elasticEnv->aatTermDescendantsIndex,
        'body' => json_decode(file_get_contents(__DIR__ . '/aat-term-descendants-mapping.json'), true),
      ]);

      foreach ($terms as $term) {
        $client->index([
          'index' => $this->elasticEnv->aatTermDescendantsIndex,
          'id' => $term['id'],
          'body' => [
            'id' => $term['id'],
            'uri' => $term['uri'],
            'prefLabel' => strtolower($term['prefLabel']),
            'descendants' => $term['descendants']
          ]
        ]);
      }
    } catch (\Exception $ex) {
      AppSettings::debugLog($ex->getMessage());
      die($ex->getMessage());
    }
  }
}
