<?php

namespace AAT;

use Application\AppSettings;
use Elastic\Query;

/**
 * LIST indices (Postman)
 * [host]:9200/_cat/indices/
 *
 * CREATE index (Postman)
 * PUT: [host]:9200/aat_concepts
 * Body with mapping: /server/classes/AAT/aat-concepts-mapping.json
 *
 * TODO:
 * Filer vi fick från Cari verkar alla sakna scopeNote och providerMappings.
 * En snabb sökning i koden visar att dessa två attribut endast används i /client/src/Subject.vue
 * Ex: https://ariadne-portal-staging.d4science.org/subject/walls?derivedSubject=walls
 * Ta kontakt med Cari och fråga om han kan ta med dessa två saknade attribut så att vi följer
 * allt vi redan har, och visar.
 *
 */

class AATConcepts {
  private $tree;
  private $currentRecordURI;
  private $recordList = [];
  private $client;
  private $elasticEnv;
  private $dataFolder = __DIR__ . '/data/aat-conc';

  /**
   * Constructor
   */
  public function __construct() {
    $this->elasticEnv = AppSettings::getSettingsEnv();
    $this->client = Query::instance()->getClient();
    $this->action();
  }

  /**
   * Main function.
   * Read files, and PUT to existing subjectIndex.
   */
  public function action() {
    if (!is_dir($this->dataFolder)) {
      die('No data folder exists.');
    }

    clearstatcache();
    ini_set('max_execution_time', '10000');
    ini_set('memory_limit', '1024M');

    try {
      $this->client->indices()->delete(['index' => $this->elasticEnv->subjectIndex]);
    } catch (\Exception $ex) {
      AppSettings::debugLog($ex->getMessage());
    }

    try {
      $this->client->indices()->create([
        'index' => $this->elasticEnv->subjectIndex,
        'body' => json_decode(file_get_contents(__DIR__ . '/aat-concepts-mapping.json'), true),
      ]);
    } catch (\Exception $ex) {
      AppSettings::debugLog($ex->getMessage());
      die($ex->getMessage());
    }

    if ($dh = opendir($this->dataFolder)) {
      $counter = 0;
      while (($conceptFile = readdir($dh)) !== false) {
        if (str_ends_with($conceptFile, '.json')) {
          /* read file, encode JSON and index to existing AAT-concpets OpenSearch index */
          $currentFile = file_get_contents($this->dataFolder.'/'.$conceptFile);
          /* File encoding forces us to do this. TODO: Investigate file encoding  */
          $currentConcept = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $currentFile),true);
          /* index current */
          $this->indexAATConcept($currentConcept);
          $counter++;
        }
      }
      closedir($dh);
      print 'Done. Imported ' . $counter . ' records.';
    } else {
      print 'Data folder not found';
    }
  }

  /**
   * Index JSON content to OpenSearch index
   */
  private function indexAATConcept($concept) {
    $concept = $concept['_source'];

    $doc = [
      'id' => $concept['id'],
      'providerMappings' => $concept['providerMappings'],
      'prefLabels' => $concept['prefLabels'],
      'altLabels' => $concept['altLabels'],
      'broader' => $concept['broader'],
      'scopeNote' => $concept['scopeNote'],
      'prefLabel' => $concept['prefLabel'],
      'uri' => $concept['uri']
    ];
    $params['index'] = $this->elasticEnv->subjectIndex;
    $params['id'] = $concept['id'];
    $params['body'] = $doc;
    $this->client->index($params);
  }
}
