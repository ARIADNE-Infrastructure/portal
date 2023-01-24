<?php

namespace Services;

use Application\AppSettings;
use Elasticsearch\ClientBuilder;

class Services {

  /**
   * Updates a service or publisher
   */
  public static function updateService () {
    $env = AppSettings::getSettingsEnv();
    $data = ['id' => intval($_POST['id'] ?? 0)];
    $client = ClientBuilder::create()->setHosts([$env->host])->build();
    $index = [
      'services' => $env->servicesIndex,
      'publishers' => $env->publishersIndex
    ][$_POST['index'] ?? ''] ?? null;

    if (empty($index)) {
      die('Invalid index.');
    }

    // delete
    if (($_POST['cmd'] ?? '') === 'delete') {
      try {
        if (empty($data['id']) || $data['id'] <= 0) {
          throw new \Exception('Invalid id.');
        }
        $client->delete([
          'index' => $index,
          'id' => $data['id']
        ]);
      } catch (\Exception $ex) {
        AppSettings::debugLog($ex->getMessage());
        die('Failed to delete item.');
      }

    // add / update
    } else {
      if ($index === $env->servicesIndex) {
        $data['title'] = trim(strip_tags($_POST['title'] ?? ''));
        $data['topic'] = trim(strip_tags($_POST['topic'] ?? ''));
        $data['description'] = trim(strip_tags($_POST['description'] ?? ''));
        $data['url'] = trim(strip_tags($_POST['url'] ?? ''));
        $data['img'] = trim(strip_tags($_POST['img'] ?? ''));

      } else { // publishers
        $data['title'] = trim(strip_tags($_POST['title'] ?? ''));
        $data['url'] = trim(strip_tags($_POST['url'] ?? ''));
        $data['img'] = trim(strip_tags($_POST['img'] ?? ''));
        $data['text'] = trim(strip_tags($_POST['text'] ?? ''));
      }

      // error if empty fields
      foreach ($data as $key => $val) {
        if (empty($val)) {
          AppSettings::debugLog($data);
          die('Error: ' . $key . ' is empty');
        }
      }

      try {
        // if no id - get max id and add new with +1
        if (empty($data['id']) || $data['id'] <= 0) {
          $result = $client->search([
            'index' => $index,
            'body'  => [
              'size' => 10000,
              '_source' => ['id'],
            ],
          ]);
          $data['id'] = max(array_map(function ($r) { return $r['_source']['id']; }, $result['hits']['hits'])) + 1;
        }
        // do the update
        $client->index([
          'index' => $index,
          'id' => $data['id'],
          'body' => $data,
        ]);
      } catch (\Exception $ex) {
        AppSettings::debugLog($ex->getMessage());
        die('Failed to update item in index');
      }
    }
    echo 'ok';
  }

  /**
   * Creates new services and publishers indexes and sets default data
   */
  public static function setDefaultServices () {
    $env = AppSettings::getSettingsEnv();
    $client = ClientBuilder::create()->setHosts([$env->host])->build();
    $indexes = [
      'services' => $env->servicesIndex,
      'publishers' => $env->publishersIndex
    ];

    // check if already exist, then exit
    try {
      if ($client->indices()->exists(['index' => $env->servicesIndex]) && $client->indices()->exists(['index' => $env->publishersIndex])) {
        die('Indexes already exist');
      }
    } catch (\Exception $ex) {
      AppSettings::debugLog($ex->getMessage());
      die;
    }

    try {
      // create new
      foreach ($indexes as $key => $index) {
        $client->indices()->create([
          'index' => $index,
          'body' => json_decode(file_get_contents(__DIR__ . '/' . $key . '-mapping.json'), true),
        ]);
      }
      // add default data
      foreach ($indexes as $key => $index) {
        $items = json_decode(file_get_contents(__DIR__ . '/default-' . $key . '.json'), true);
        $id = 1;
        foreach ($items as $item) {
          $client->index([
            'index' => $index,
            'id' => $id,
            'body' => array_merge($item, ['id' => $id]),
          ]);
          $id++;
        }
      }
    } catch (\Exception $ex) {
      AppSettings::debugLog($ex->getMessage());
      die($ex->getMessage());
    }
    echo 'Done.';
  }
}
