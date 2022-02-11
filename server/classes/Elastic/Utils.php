<?php

namespace Elastic;

use \geoPHP\geoPHP;

class Utils {


  private CONST TOP_LEFT = 315; // Bearing Degrees - North West
  private CONST BOTTOM_RIGHT = 135; // Bearing Degrees - South East
  private CONST POINT_EXTEND_KM = 0.5;

  /**
    * Escape lucene special chars
    */
  public static function escapeLuceneValue ($str, $slashes = true) {
    $lucene = '<>{}[]=&|!^?\\' . ($slashes ? '/' : '');
    $str = str_replace(str_split($lucene), ' ', $str);
    return trim(preg_replace('/\s+/', ' ', $str));
  }

  /**
   * Returns if valid resource id
   */
  public static function isValidId ($id) {
    return preg_match('/^[a-zA-Z0-9\-]+$/', $id);
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
        $xml->addChild($key, htmlspecialchars($val));
      }
    }
  }

}

