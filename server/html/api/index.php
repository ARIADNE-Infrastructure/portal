<?php

require '../../vendor/autoload.php';

spl_autoload_register(function ($class_name) {
  $class_name = '../../classes/' . str_replace("\\", DIRECTORY_SEPARATOR, $class_name);
  include $class_name . '.php';
});

use Application\Contact;
use Elastic\Query;
use Elastic\Utils;
use Elastic\DummyResource;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header_remove('X-Powered-By');

$settings = json_decode(file_get_contents('../../classes/settings.json'));

// Hide all errors and warnings for prod environment
if( strtolower($settings->environment->elasticsearchEnv) === 'prod' ) {
  error_reporting(0); 
}

$logger = new Logger('main');
if ($settings->environment->logLevel !== 'NONE') {
  $logLevel = $settings->environment->logLevel;
  $handler = new StreamHandler($settings->environment->logPath, constant("Monolog\Logger::$logLevel"));
  $handler->setFormatter(new LineFormatter(null, null, false, true));
  $logger->pushHandler($handler);
}

$q = new Query($settings, $logger);

$base = 'api/';
$parts = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = substr($parts, strrpos($parts, $base) + strlen($base));
$uriParts = explode('/', $parts);

switch ($uriParts[0]) {
  case 'getAllRecords':
    echo json_encode($q->getAllRecords());
    break;

  case 'getRecord':
    if (($uriParts[1] ?? '') === 'dummyRecord') {
      $res = $q->getNormalizer()->splitLanguages(DummyResource::getDummy());
    } else {
      $res = $q->getRecord($uriParts[1] ?? '');
    }
    if (($uriParts[2] ?? '') === 'xml') {
      header('Content-Type: application/xml');
      echo Utils::getRecordAsXML($res);
    } else {
      echo json_encode($res, JSON_PRETTY_PRINT);
    }
    break;

  case 'getSubject':
    echo json_encode($q->getSubject($uriParts[1] ?? ''));
    break;

  case 'search':
    echo json_encode($q->search());
    break;

  case 'autocomplete':
    echo json_encode($q->autocomplete());
    break;

  case 'autocompleteFilter':
    echo json_encode($q->autocompleteFilter());
    break;

  case 'getMiniMapData':
    echo json_encode($q->getMiniMapData());
    break;

  case 'getMapData':
    echo json_encode($q->getMiniMapData());
    break;

  case 'getSearchAggregationData':
    echo json_encode($q->getSearchAggregationData());
    break;

  case 'getPeriodRegions':
    echo json_encode($q->getPeriodRegions());
    break;

  case 'getPeriodsForCountry':
    echo json_encode($q->getPeriodsForCountry());
    break;

  case 'getNearbySpatialResources':
    echo json_encode($q->getNearbySpatialResources($uriParts[1] ?? ''), true);
    break;

  case 'mail':
    header('Access-Control-Allow-Methods: GET, POST');
    header('Access-Control-Allow-Headers: Content-Type');
    $contact = new Contact($settings, $logger);
    echo json_encode($contact->sendMail());
    break;

  case 'updatePeriods':
    header('Content-Type: text/html; charset=utf-8');
    if ($_GET['id'] === $settings->environment->periodsId) {
      new Periodo\Periodo($settings);
    }
    break;

  case 'getTotalRecordsCount':
    echo json_encode($q->getTotalRecordsCount());
    break;

  default:
    $esHost = $settings->elasticsearchEnv->{$settings->environment->elasticsearchEnv}->host;
    $esIndex = $settings->elasticsearchEnv->{$settings->environment->elasticsearchEnv}->index;
    $esAATIndex = $settings->elasticsearchEnv->{$settings->environment->elasticsearchEnv}->subject_index;

    $periodHost = $settings->elasticsearchEnv->{$settings->environment->elasticsearchEnv}->periodHost;
    $periodIndex = $settings->elasticsearchEnv->{$settings->environment->elasticsearchEnv}->periodIndex;
    $periodAuthorities = $settings->periodAuthorities;

    echo json_encode([
      'Api_Title' => 'ARIADNE PORTAL',
      'ES_Index' => $esHost . '/' . $esIndex,
      'ES_AAT_Index' => $esHost . '/' . $esAATIndex,
      'PERIOD_Index' => $periodHost . '/' . $periodIndex,
      'PERIOD_Authorities' => $periodAuthorities,
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
    ], JSON_PRETTY_PRINT);
}
