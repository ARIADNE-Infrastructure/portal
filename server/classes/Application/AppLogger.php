<?php

namespace Application;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

use Elastic\AppSettings;

class AppLogger {

  public $log = null;

  public function __construct() {

    $settings = (new AppSettings())->getSettings();

    $this->log = new Logger('main');
    $this->log->pushHandler(new StreamHandler('../'.$settings->environment->logPath, Logger::DEBUG));
    
  }

  public function info($log) {
    $this->log->info($log);
  }

}