<?php
require '../vendor/autoload.php';

header('Content-Type: application/json');

spl_autoload_register(function ($class_name) {
  $class_name = '../classes/' . str_replace("\\", DIRECTORY_SEPARATOR, $class_name);
  include $class_name . '.php';
});


use Elastic\Query;
use Elastic\Utils;

$q = new Query();

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/', $uri);

/* Dispatch request */
switch ($uri[1] ?? '') {
  case 'getAllRecords':
    print(json_encode($q->getAllRecords()));
    break;

  case 'getRecord':
    if (isset($uri[2])) {
      $res = $q->getRecord($uri[2], 'index');

      if (isset($uri[3]) && $uri[3] === 'xml') {
        header('Content-Type: application/xml');
        print(Utils::getRecordAsXML($res));

      } else {
        print(json_encode($res, JSON_PRETTY_PRINT));
      }
    } else {
      showInfo();
    }
    break;

  case 'search':
    print(json_encode($q->search()));
    break;

  case 'autocomplete':
    print(json_encode($q->autocomplete()));
    break;

  case 'cloud':
    print(json_encode($q->getWordCloudData()));
    break;

  default:
    showInfo();
}

/**
 * Show info about endpoint
 */
function showInfo() {
  header('Content-Type: text/html; charset=utf-8');
  print(
    ' Valid REST endpoints<br><br>'.
    ' &bull; getRecord/[id]<br>'.
    ' &bull; getRecord/[id]/xml<br>'.
    ' &bull; getAllRecords<br>' .
    ' &bull; cloud<br>' .
    ' &bull; search?q=[searchString]<br>' .
    ' &bull; autocomplete?q=[searchString]'
  );
}
