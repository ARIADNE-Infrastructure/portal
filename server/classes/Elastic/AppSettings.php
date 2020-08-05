<?php

namespace Elastic;

class AppSettings {

  private $appSettings;

  public function __construct() {
    $settings = json_decode(file_get_contents('../classes/settings.json'));
    $type = !empty($_ENV['IS_PRODUCTION']) ? 'prod' : 'dev';
    $this->appSettings = $settings->$type;
  }

  public function getSettings () {
    return $this->appSettings;
  }
}
