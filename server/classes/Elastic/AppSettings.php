<?php

namespace Elastic;

class AppSettings {

  private $appSettings;

  public function __construct() {
    $this->appSettings = json_decode(file_get_contents('../../classes/settings.json'));
  }

  public function getSettings () {
    return $this->appSettings;
  }
}
