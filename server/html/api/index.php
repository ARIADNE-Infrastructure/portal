<?php
//phpinfo(); exit;
//xdebug_info(); exit;

require '../../vendor/autoload.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

spl_autoload_register(function ($class_name) {
  $class_name = '../../classes/' . str_replace("\\", DIRECTORY_SEPARATOR, $class_name);
  include $class_name . '.php';
});

use Elastic\Query;
use Elastic\Utils;
use Application\Contact;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = substr($uri, strrpos($uri, 'api/') + 4); // get all after api/
$uri = explode('/', $uri); // explode and get rest calls to array

/* Dispatch request */
switch ($uri[0] ?? '') {

  case 'getAllRecords':
    echo json_encode(Query::instance()->getAllRecords());
    break;

  case 'getRecord':
    if (isset($uri[1])) {
      $res = Query::instance()->getRecord($uri[1], 'index');
      if (isset($uri[2]) && $uri[2] === 'xml') {
        header('Content-Type: application/xml');
        echo Utils::getRecordAsXML($res);

      } else {
        echo json_encode($res, JSON_PRETTY_PRINT);
      }
    } else {
      showInfo();
    }
    break;

  case 'getSubject':
    if (isset($uri[1])) {
      echo json_encode(Query::instance()->getSubject($uri[1]));
    }
    break;

  case 'search':
    echo json_encode(Query::instance()->search());
    break;

  case 'autocomplete':
    echo json_encode(Query::instance()->autocomplete());
    break;

  case 'cloud':
    echo json_encode(Query::instance()->getWordCloudData());
    break;

  case 'mail':
    header('Access-Control-Allow-Methods: GET, POST');
    header('Access-Control-Allow-Headers: Content-Type');
    echo json_encode(Contact::sendMail());
    break;

  default:
    showInfo();
}

/**
 * Show info about endpoint
 */
function showInfo() {
  header('Content-Type: text/html; charset=utf-8');
  echo 'Valid REST endpoints<br><br>'.
    '&bull; getRecord/[id]<br>'.
    '&bull; getRecord/[id]/xml<br>'.
    '&bull; getAllRecords<br>' .
    '&bull; getSubject/[id]<br>' .
    '&bull; cloud<br>' .
    '&bull; mail<br>' .
    '&bull; search?q=[searchString]<br>' .
    '&bull; autocomplete?q=[searchString]';
}
