<?php

if (($_SERVER['SERVER_NAME'] ?? '') !== 'localhost') {
  error_reporting(0);
}

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require '../../vendor/autoload.php';
spl_autoload_register(function ($cl) {
  require '../../classes/' . str_replace("\\", DIRECTORY_SEPARATOR, $cl) . '.php';
});

use Application\Contact;
use Application\AppSettings;
use Elastic\Query;
use Elastic\Utils;

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header_remove('X-Powered-By');

$base = 'api/';
$parts = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = substr($parts, strrpos($parts, $base) + strlen($base));
$uriParts = explode('/', $parts);

switch ($uriParts[0]) {
  case 'getSubject': echo json_encode(Query::instance()->getSubject($uriParts[1] ?? '')); break;
  case 'search': echo json_encode(Query::instance()->search()); break;
  case 'autocomplete': echo json_encode(Query::instance()->autocomplete()); break;
  case 'autocompleteFilter': echo json_encode(Query::instance()->autocompleteFilter()); break;
  case 'getMiniMapData': echo json_encode(Query::instance()->getMiniMapData()); break;
  case 'getSearchAggregationData': echo json_encode(Query::instance()->getSearchAggregationData()); break;
  case 'getPeriodRegions': echo json_encode(Query::instance()->getPeriodRegions()); break;
  case 'getPeriodsForCountry': echo json_encode(Query::instance()->getPeriodsForCountry()); break;
  case 'getTotalRecordsCount': echo json_encode(Query::instance()->getTotalRecordsCount()); break;
  case 'getAllServicesAndPublishers': echo json_encode(Query::instance()->getServicesAndPublishers()); break;
  case 'maybeUpdatePeriods': Query::instance()->maybeUpdatePeriods(); break;

  case 'getRecord':
    if (($uriParts[2] ?? '') === 'xml') {
      header('Content-Type: application/xml');
      echo Utils::getRecordAsXML(Query::instance()->getRecord($uriParts[1]));
    } else if (!empty($uriParts[1])) {
      echo json_encode(Query::instance()->getRecord($uriParts[1]), JSON_PRETTY_PRINT);
    }
    break;

  case 'mail':
    header('Access-Control-Allow-Methods: GET, POST');
    header('Access-Control-Allow-Headers: Content-Type');
    echo json_encode((new Contact())->sendMail());
    break;

  default:
    header('Content-Type: text/html; charset=utf-8');
    if (AppSettings::hasValidSecret()) {
      switch ($uriParts[0]) {
        case 'updatePeriods': new Periodo\Periodo(false); break;
        case 'updateAATDescendants': (new AAT\AATDescendants())->createTree(); break;
        case 'updateAATConcepts': new AAT\AATConcepts(); break;
        case 'buildLocalIndex': new \Elastic\LocalBuilder(); break;
        case 'setServices': Services\Services::setDefaultServices(); break;
        case 'updateServices':
            header('Access-Control-Allow-Methods: GET, POST');
            header('Access-Control-Allow-Headers: Content-Type');
            Services\Services::updateService();
            break;
      }
  }
}
