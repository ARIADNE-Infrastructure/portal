<?php

namespace Elastic;

use Elasticsearch\ClientBuilder;
use Application\AppSettings;

/**
 * Builds a local copy of remote index
 * Make sure elasticsearch in docker-compose is active
 * Set query param "max" for max amount
 */

class LocalBuilder {
  private $elasticEnv;
  private $localClient;
  private $remoteClient;

  /**
   * constructor
   */
  public function __construct () {
    if (AppSettings::getSettings()->environment->elasticsearchEnv !== 'local') {
      die('Not using local env');
    }

    $this->elasticEnv = AppSettings::getSettingsEnv();
    $this->localClient = ClientBuilder::create()->setHosts([$this->elasticEnv->host])->build();
    $this->remoteClient = ClientBuilder::create()->setHosts([$this->elasticEnv->remoteHost])->build();

    ini_set('max_execution_time', '10000');
    ini_set('memory_limit', '1024M');
    $this->indexAllDocuments();
  }

  /**
   * Gets mapping and all records from remote host, and indexes in local index
   */
  private function indexAllDocuments () {
    // get mapping
    $mapping = json_decode(file_get_contents(sprintf('http://%s/%s', $this->elasticEnv->remoteHost, $this->elasticEnv->index)), true);
    if (empty($mapping)) {
      die('Mapping is empty');
    }

    // delete old index
    try {
      $this->localClient->indices()->delete(['index' => $this->elasticEnv->index]);
    } catch (\Exception $ex) {
      AppSettings::debugLog($ex->getMessage());
    }

    // create new index
    try {
      $this->localClient->indices()->create([
        'index' => $this->elasticEnv->index,
        'body' => [
          'settings' => [
            'index' => [
              'analysis' => $mapping[$this->elasticEnv->index]['settings']['index']['analysis'],
            ],
          ],
          'mappings' => $mapping[$this->elasticEnv->index]['mappings'],
        ],
      ]);
    } catch (\Exception $ex) {
      die(sprintf("Failed to create index: %s\n", $ex->getMessage()));
    }

    // start inserting records
    $total = 0;
    $result = null;
    $scrollId = null;
    $isFirst = true;
    $max = intval($_GET['max'] ?? 0);

    try {
      while (true) {
        if ($isFirst) {
          $isFirst = false;
          $result = $this->remoteClient->search([
            'index' => $this->elasticEnv->index,
            'scroll' => '30m',
            'body'  => [
              'track_total_hits' => true,
              'size' => 2000,
              'query' => [
                'match_all' => new \stdClass(),
              ],
            ],
          ]) ?? [];
          $scrollId = $result['_scroll_id'] ?? null;
        } else {
          $result = $this->remoteClient->scroll(['scroll_id' => $scrollId, 'scroll' => '30m']) ?? [];
          $scrollId = $result['_scroll_id'] ?? null;
        }

        if (!empty($result['hits']['hits'])) {
          foreach ($result['hits']['hits'] as $hit) {
            $this->localClient->index([
              'index' => $this->elasticEnv->index,
              'id' => $hit['_id'],
              'body' => $hit['_source'],
            ]);
            $total++;
          }
        } else {
          break;
        }
        if ($max > 0 && $total > $max) {
          break;
        }
      }
    } catch (\Exception $ex) {
      die(sprintf("Error indexing documents: %s\n", $ex->getMessage()));
    }
    printf("Success! Indexed %d records\n", $total);
  }
}
