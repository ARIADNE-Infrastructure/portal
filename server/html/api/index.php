<?php

require '../../vendor/autoload.php';

spl_autoload_register(function ($class_name) {
  $class_name = '../../classes/' . str_replace("\\", DIRECTORY_SEPARATOR, $class_name);
  include $class_name . '.php';
});

use Application\App;
use Application\Contact;
use Elastic\DummyResource;

//use Periodo\Periodo;
//$p = new Periodo();

$settings = json_decode(file_get_contents('../../classes/settings.json'));
$app = new App('api/', $settings);

$app->route('getAllRecords', function ($q, $parts) {
  $this->json($q->getAllRecords());
});

$app->route('getNearbySpatialResources', function ($q, $parts) {
  $this->json($q->getNearbySpatialResources($parts[0] ?? ''), true);
});

$app->route('getRecord', function ($q, $parts) {
  $slug = $parts[0] ?? '';

  if ($slug === 'dummyRecord') {
    $res = $q->getNormalizer()->splitLanguages(DummyResource::getDummy());
  } else {
    $res = $q->getRecord($slug);
  }

  if ($parts[1] ?? '' === 'xml') {
    $this->xml($res);
  } else {
    $this->json($res, true);
  }
});

$app->route('getSubject', function ($q, $parts) {
  $this->json($q->getSubject($parts[0] ?? ''));
});

$app->route('search', function ($q, $parts) {
  $this->json($q->search());
});

$app->route('autocomplete', function ($q, $parts) {
  $this->json($q->autocomplete());
});

$app->route('autocompleteFilter', function ($q, $parts) {
  $this->json($q->autocompleteFilter());
});

$app->route('getMiniMapData', function ($q, $parts) {
  $this->json($q->getMiniMapData());
});

$app->route('getMapData', function ($q, $parts) {
  $this->json($q->getMiniMapData());
});

$app->route('getSearchAggregationData', function ($q, $parts) {
  $this->json($q->getSearchAggregationData());
});

$app->route('getPeriodsCountryAggregationData', function ($q, $parts) {
    // $this->q->setClient($this->settings->elasticsearchEnv->{$this->settings->environment->elasticsearchEnv}->periodHost);
    $this->json($q->getPeriodsCountryAggregationData());
  },
  $settings->elasticsearchEnv->{$settings->environment->elasticsearchEnv}->periodHost
);

$app->route('getPeriodsForCountry', function ($q, $parts) {
    // $this->q->setClient($this->settings->elasticsearchEnv->{$this->settings->environment->elasticsearchEnv}->periodHost);
    $this->json($q->getPeriodsForCountry());
  },
  $settings->elasticsearchEnv->{$settings->environment->elasticsearchEnv}->periodHost
);

$app->route('mail', function ($q, $parts) {
  header('Access-Control-Allow-Methods: GET, POST');
  header('Access-Control-Allow-Headers: Content-Type');
  $contact = new Contact($this->getSettings(), $this->getLogger());
  $this->json($contact->sendMail());
});

// 404
$app->route('*', function () {
  $settings = $this->getSettings();
  $esHost = $settings->elasticsearchEnv->{$settings->environment->elasticsearchEnv}->host;
  $esIndex = $settings->elasticsearchEnv->{$settings->environment->elasticsearchEnv}->index;
  $esAATIndex = $settings->elasticsearchEnv->{$settings->environment->elasticsearchEnv}->subject_index;

  $this->json([
    'Api_Title' => 'ARIADNE PORTAL',
    'ES_Index' => $esHost . '/' . $esIndex,
    'ES_AAT_Index' => $esHost . '/' . $esAATIndex,
    'Valid_REST_endpoints' => [
      'api/getRecord/{id}',
      'api/getRecord/{id}/xml',
      'api/getAllRecords',
      'api/getSubject/{id}',
      'api/getNearbySpatialResources/{id}',
      'api/search?q={searchString}',
      'api/autocomplete?q={searchString}',
      'api/autocompleteFilter?q={searchString}&filter={filterName}',
      'api/mail',
    ],
  ], true);
});

$app->run();
