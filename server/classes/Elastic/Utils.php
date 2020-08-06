<?php

namespace Elastic;

class Utils {

  /**
    * Escape lucene special chars
    */
  public static function escapeLuceneValue ($string) {
    $match = array('/', '\\');
    $replace = array(' ', '\\\\');
    $string = str_replace($match, $replace, $string);

    return $string;
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
