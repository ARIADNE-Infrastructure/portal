<?php

namespace Application;

class AppSettings {
  private static $settings = null;

  /**
   * return settings file as JSON
   */
  public static function getSettings () {
    if (!self::$settings) {
      self::$settings = json_decode(file_get_contents('../../classes/settings.json'));
    }
    return self::$settings;
  }

  /**
   * Returns active settings env
   */
  public static function getSettingsEnv () {
    $settings = self::getSettings();
    return $settings->elasticsearchEnv->{$settings->environment->elasticsearchEnv};
  }

  /**
   * Returns if valid secret get param is set for update actions
   */
  public static function hasValidSecret () {
    return !empty($_GET['id']) && $_GET['id'] === self::getSettings()->environment->portalUpdateSecret;
  }

  /**
   * Returns if logging is active
   */
  public static function isLogging () {
    return self::getSettings()->environment->logActive;
  }

  /**
   * Simple debug logging
   */
  public static function debugLog ($data) {
    if (!self::isLogging() || empty($data)) {
      return;
    }
    if (!is_string($data)) {
      $data = json_encode($data);
      if (empty($data)) {
        return;
      }
    }
    file_put_contents(self::$settings->environment->logPath, trim($data) . "\n", FILE_APPEND);
  }
}
