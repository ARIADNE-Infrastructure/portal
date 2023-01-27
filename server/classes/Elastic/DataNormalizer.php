<?php

namespace Elastic;

/**
 * Methods for fixing outgoing data.
 */
class DataNormalizer {
  private $defaultLanguage;

  public function __construct($settings) {
    $this->defaultLanguage = $settings->environment->defaultLanguage;
  }

  /**
   * splitLanguages
   */
  public function splitLanguages($resource) {
    $fields = array('title','description');

    // Set resource language
    $resourceLanguage = $this->defaultLanguage;
    if(isset($resource['language'])) {
      $resourceLanguage = $resource['language'];
    }

    foreach ($resource as $fieldName=>$fieldData) {
      if(in_array($fieldName, $fields)) {
        $r = $this->splitFieldToDefaultLanguage($fieldData, $fieldName, $resourceLanguage);
        $resource = array_replace(
          $resource,
          $r
        );
      }
    }
    return $resource;
  }

  /**
   * splitFieldToDefaultLanguage
   */
  private function splitFieldToDefaultLanguage($fieldData, $fieldName, $resourceLanguage) {
    $result = [];

    if (empty($fieldData) || count($fieldData) == 1) {
      // Empty or only one, return
      $result[$fieldName] = $fieldData[0];
      return $result;

    } else {
      $defaultLangKey = array_search($this->defaultLanguage, array_column($fieldData, 'language'));
      if( $defaultLangKey !== false ) {
        // Set default
        $result[$fieldName] = $fieldData[$defaultLangKey];
        unset($fieldData[$defaultLangKey]);

      } else {
        // Default language is not there, check for resource language
        $resourceLangKey = array_search($resourceLanguage, array_column($fieldData, 'language'));

        if ($resourceLangKey !== false) {
          // Resource language is there. Set as default
          $result[$fieldName] = $fieldData[$resourceLangKey];
          unset($fieldData[$resourceLangKey]);
        } else {
          // Nor default nor resource language found. Default to first available/any
          $result[$fieldName] = $fieldData[0];
          unset($fieldData[0]);
        }
      }

      if (!empty($fieldData)) {
        // Add rest to 'Other' property
        $result[$fieldName.'Other'] = array_values($fieldData);
      }
      return $result;
    }
  }

  /**
   * Beauitify result aggregations that have zero buckets.
   *
   * In cases where aggs has buckets[0] as result from Elastic we push
   * {"key":"<aggregation_key_value>","doc_count":0} instead of returning a blank array
   *
   * This is manipulating Elastic resultsets and exist only so that frontend can filter and
   * render chosen filters correctlly.
   */
  public function normalizeAggs($result, $aggregationsReqFilter) {
    if (isset($result['aggregations'])) {
      foreach ($result['aggregations'] as $aggKey => $aggValue) {
        if (!empty($aggregationsReqFilter[$aggKey]) && empty($aggValue['buckets'])) {
          foreach ($aggregationsReqFilter[$aggKey] as $key => $value) {
            // This means that buckets is an empty array.
            // Put values into it to simplify client side rendering of chosen filters/aggs by user.
            $result['aggregations'][$aggKey]['buckets'][] = ['key' => $value, 'doc_count' => 0];
          }
        }
      }
    }
    return $result;
  }
}
