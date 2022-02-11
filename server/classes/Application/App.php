<?php

namespace Application;

use Elastic\Query;
use Elastic\Utils;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * App class - routing, settings & logging
 */

class App {
  private $routes = [];
  private $settings;
  private $logger;
  private $uriParts;
  private $q;

  public function __construct ($base) {
    $parts = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $parts = substr($parts, strrpos($parts, $base) + strlen($base));
    $this->uriParts = explode('/', $parts);

    $this->settings = json_decode(file_get_contents('../../classes/settings.json'));

    $this->logger = new Logger('main');
    if ($this->settings->environment->logLevel !== 'NONE') {
      $logLevel = $this->settings->environment->logLevel;
      $handler = new StreamHandler($this->settings->environment->logPath, constant("Monolog\Logger::$logLevel"));
      $handler->setFormatter(new LineFormatter(null, null, false, true));
      $this->logger->pushHandler($handler);
    }

    $this->q = new Query($this->settings, $this->logger);
  }

  // returns settings
  public function getSettings () {
    return $this->settings;
  }

  // returns logger
  public function getLogger () {
    return $this->logger;
  }

  // adds new route
  public function route ($route, $callback) {
    $this->routes[$route] = $callback->bindTo($this, __CLASS__);
  }

  // prints json
  public function json ($data, $pretty = false) {
    echo json_encode($data, $pretty ? JSON_PRETTY_PRINT : 0);
  }

  // prints xml
  public function xml ($data) {
    header('Content-Type: application/xml');
    echo Utils::getRecordAsXML($data);
  }

  // runs app & checks routes
  public function run () {
    header('Access-Control-Allow-Origin: *');
    header('X-Robots-Tag: noindex, nofollow');
    header('Content-Type: application/json');
    header_remove('X-Powered-By');

    foreach ($this->routes as $route => $callback) {
      if ($route === $this->uriParts[0]) {
        $callback($this->q, array_slice($this->uriParts, 1));
        return;
      }
    }

    $this->routes['*']();
  }
}
