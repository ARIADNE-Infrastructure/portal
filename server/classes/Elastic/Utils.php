<?php

namespace Elastic;

class Utils {
  /**
   * Escape lucene special chars
   */
  public static function escapeLuceneValue ($str, $slashes = true) {
    $lucene = '<>{}[]=&|!^?\\' . ($slashes ? '/' : '');
    $str = str_replace(str_split($lucene), ' ', $str);
    return trim(preg_replace('/\s+/', ' ', $str));
  }

  /**
   * Returns if locations are equals
   */
  public static function isLocationDoublet ($a, $b) {
    $aLoc = $a['geopoint'];
    $bLoc = $b['geopoint'];
    return (round($aLoc['lat'], 4) === round($bLoc['lat'], 4) && round($aLoc['lon'], 4) === round($bLoc['lon'], 4)) ||
      (!empty($a['placeName']) && !empty($b['placeName']) && strtolower($a['placeName']) === strtolower($b['placeName']));
  }

  /**
   * Gets a record as xml
   */
  public static function getRecordAsXML ($record) {
    $xml = new \SimpleXMLElement('<root/>');
    self::recordToXml($record, $xml);
    $dom = dom_import_simplexml($xml)->ownerDocument;
    $dom->formatOutput = true;
    return $dom->saveXML();
  }

  /**
   * Recursive array to xml
   */
  private static function recordToXml ($arr, &$xml){
    foreach ($arr as $key => $val) {
      if (is_numeric($key)) {
        $key = 'item';
      }
      if (is_array($val)) {
        $label = $xml->addChild($key);
        self::recordToXml($val, $label);
      } else {
        $xml->addChild($key, htmlspecialchars($val ?? ''));
      }
    }
  }

  /**
   * Split languages
   */
  public static function splitLanguages ($resource, $defaultLanguage) {
    $fields = array('title', 'description');
    $resourceLanguage = isset($resource['language']) ? $resource['language'] : $defaultLanguage; // Set resource language
    foreach ($resource as $fieldName=>$fieldData) {
      if (in_array($fieldName, $fields)) {
        $result = [];
        if (empty($fieldData) || count($fieldData) == 1) { // Empty or only one, return
          $result[$fieldName] = $fieldData[0];
        } else {
          $defaultLangKey = array_search($defaultLanguage, array_column($fieldData, 'language'));
          if ($defaultLangKey !== false) { // Set default
            $result[$fieldName] = $fieldData[$defaultLangKey];
            unset($fieldData[$defaultLangKey]);
          } else { // Default language is not there, check for resource language
            $resourceLangKey = array_search($resourceLanguage, array_column($fieldData, 'language'));
            if ($resourceLangKey !== false) { // Resource language is there. Set as default
              $result[$fieldName] = $fieldData[$resourceLangKey];
              unset($fieldData[$resourceLangKey]);
            } else { // Nor default nor resource language found. Default to first available/any
              $result[$fieldName] = $fieldData[0];
              unset($fieldData[0]);
            }
          }
          if (!empty($fieldData)) { // Add rest to 'Other' property
            $result[$fieldName.'Other'] = array_values($fieldData);
          }
        }
        $resource = array_replace($resource, $result);
      }
    }
    return $resource;
  }

  /**
   * Normalize result aggregations that have zero buckets.
   */
  public static function normalizeAggs ($result, $aggregationsReqFilter) {
    if (isset($result['aggregations'])) {
      foreach ($result['aggregations'] as $aggKey => $aggValue) {
        if (!empty($aggregationsReqFilter[$aggKey]) && empty($aggValue['buckets'])) {
          foreach ($aggregationsReqFilter[$aggKey] as $key => $value) {
            $result['aggregations'][$aggKey]['buckets'][] = ['key' => $value, 'doc_count' => 0];
          }
        }
      }
    }
    return $result;
  }
}

