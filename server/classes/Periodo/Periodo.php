<?php

namespace Periodo;

use Application\AppSettings;
use Elastic\Query;

class Periodo {
  private $client;
  private $index;
  private $recordCount = 0;
  private $dataFile = __DIR__ . '/cached-periods.json';
  private $autoUpdateTime = '+1 day';

  /**
   * constructor
   */
  public function __construct ($isAutoUpdate) {
    ini_set('max_execution_time', '10000');
    ini_set('memory_limit', '1024M');

    // Setup Elastic client
    $time = time();
    if (!$isAutoUpdate) {
      echo 'Start: ' . date('H:i:s');
    }

    $elasticEnv = AppSettings::getSettingsEnv();
    $this->index = $elasticEnv->periodIndex;
    $this->client = Query::instance()->getClient();

    if ($isAutoUpdate) { // a bit risky to delete & create indexes on auto update - so just reset all timetamps
      $this->resetTimeStamps();
    } else {
      if (!empty($_GET['getRemote'])) { // use this flag to update periods cache
        $this->updatePeriodsFromRemote();
      }
      $this->createIndex();
    }

    // Import all periods
    $this->action();

    if (!$isAutoUpdate) {
      echo '<br>Completed: ' . date("H:i:s");
      echo '<br>Time: ' . round(time() - $time, 2) . 's';
      echo '<br>Added: '. $this->recordCount;
      echo '<br>Index: ' . $elasticEnv->host . '/' . $elasticEnv->periodIndex;
      echo '<br>Source: ' . AppSettings::getSettings()->environment->periodsRemotePath;
      echo '<br>Cached: ' . (empty($_GET['getRemote']) ? 'yes' : 'no');
    }
  }

  /**
   * Main function
   */
  private function action () {
    $currentPeriodsQuery = [
      'size' => 0,
      'aggs' => [
        'temporal' => [
          'nested' => [
            'path' => 'temporal',
          ],
          'aggs' => [
            'temporal' => [
              'terms' => [
                'field' => 'temporal.uri.raw',
                'size' => 10000,
              ],
            ],
          ],
        ],
      ]
    ];
    $allPeriods = Query::instance()->elasticDoSearch($currentPeriodsQuery)['aggregations']['temporal']['temporal']['buckets'] ?? [];
    $allPeriodIds = [];
    foreach ($allPeriods as $periodId) {
      $arr = explode('/', $periodId['key']);
      if (in_array('n2t.net', $arr)) {
        $allPeriodIds[end($arr)] = $periodId['doc_count'];
      }
    }
    $allPeriods = null;

    $periods = json_decode(file_get_contents($this->dataFile), true);
    $time = strtotime($this->autoUpdateTime);

    foreach ($allPeriodIds as $id => $total) {
      $period = $periods[$id] ?? null;
      if (!empty($period)) {
        $period['timestamp'] = $time;
        $period['total'] = $total;
        $this->client->index([
          'index' => $this->index,
          'id' => $period['id'],
          'body' => $period,
        ]);
        $this->recordCount++;
      }
    }
  }

  /**
   * Create index in elastic.
   */
  private function createIndex () {
    $mapping = json_decode(file_get_contents(__DIR__ . '/periodo-mapping.json'), true);
    try {
      $this->client->indices()->delete(['index' => $this->index]);
    } catch (\Exception $e) {
      AppSettings::debugLog($e->getMessage());
      echo '<br>Error: ' . $e->getMessage();
    }
    try {
      $this->client->indices()->create([
        'index' => $this->index,
        'body' => $mapping,
      ]);
    } catch (\Exception $ex) {
      AppSettings::debugLog($ex->getMessage());
      echo '<br>Failed to create index: ' . $ex->getMessage();
    }
  }

  /**
   * Resets all timestamps on auto update (instead of creating a new index)
   * This is done so auto update doesn't accidently run multiple times
   */
  private function resetTimeStamps () {
    try {
      $this->client->updateByQuery([
        'index' => $this->index,
        'body' => [
          'script' => [
            'source' => 'ctx._source.timestamp = 0',
          ],
          'query' => [
            'match_all' => new \stdClass(),
          ],
        ],
      ]);
    } catch (\Exception $ex) {
      AppSettings::debugLog($ex->getMessage());
    }
  }

  // gets data from remote & saves to disk, also optimizes the file size (excludes all not used)
  private function updatePeriodsFromRemote () {
    $rawData = json_decode(file_get_contents(AppSettings::getSettings()->environment->periodsRemotePath), true);
    if (empty($rawData)) {
      AppSettings::debugLog('updatePeriodsFromRemote - Error getting data from remote.');
      die('Error getting data from remote.');
    }
    $data = [];
    foreach ($rawData['authorities'] as $authority) {
      if (!empty($authority) && !empty($authority['periods'])) {
        foreach ($authority['periods'] as $period) {
          if (!empty($period['id']) && !empty($period['label']) && !empty($period['languageTag'])) {
            $data[$period['id']] = [
              'id' => $period['id'] ?? '',
              'label' => $period['label'] ?? '',
              'authority' => $authority['source']['title'] ?? ($authority['source']['partOf']['title'] ?? 'N/A'),
              'language' => $period['language'] ?? '',
              'languageTag' => $period['languageTag'] ?? '',
              'localizedLabels' => $this->getLocalizedLabels($period['localizedLabels'] ?? []),
              'spatialCoverage' => $this->getSpatialCoverage($period['spatialCoverage'] ?? []),
              'start' => ['year' => $period['start']['in']['year'] ?? 0, 'label' => $period['start']['label'] ?? ''],
              'stop' => ['year' => $period['stop']['in']['year'] ?? 0, 'label' => $period['stop']['label'] ?? ''],
            ];
          }
        }
      }
    }
    if (empty($data) || !file_put_contents($this->dataFile, json_encode($data))) {
      AppSettings::debugLog('updatePeriodsFromRemote - Error saving remote data.');
      die('Error saving remote data');
    }
  }

  private function getLocalizedLabels ($labelArr) {
    $labels = [];
    foreach ($labelArr as $key => $values) {
      foreach ($values as $val) {
        if (!empty(trim($key)) && !empty(trim($val))) {
          $labels[] = [
            'language' => trim($key),
            'label' => trim($val),
          ];
        }
      }
    }
    return $labels;
  }

  private function getSpatialCoverage ($spatials) {
    $labels = [];
    foreach ($spatials as $key => $val) {
      if (!empty(trim($val['id'])) && !empty(trim($val['label']))) {
        $labels[] = [
          'id' => trim($val['id']),
          'label' => trim($val['label']),
        ];
      }
    }
    return $labels;
  }
}
