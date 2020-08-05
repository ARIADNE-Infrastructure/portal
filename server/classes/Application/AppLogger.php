<?php

namespace Application;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class AppLogger {

  public $log = null;

  public function __construct() {

    $this->log = new Logger('main');
    $this->log->pushHandler(new StreamHandler('../logs/app.log', Logger::DEBUG));
    
  }

}