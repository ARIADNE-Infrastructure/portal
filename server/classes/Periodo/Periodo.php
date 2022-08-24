<?php

namespace Periodo;
require '../../vendor/autoload.php';

use Elasticsearch\ClientBuilder;
use Exception;
use Elasticsearch\Common\Exceptions\Missing404Exception;

use \geoPHP\geoPHP;

class Periodo {

  private $settings;
  private $client;
  private $index = 'ariadneplus_test_periodo';
  private $validAuthorities;
  private $recordCount = 0;
  private $dataFile = '';

  /**
   * constructor
   */
  public function __construct($settings) {

    // Setup Elastic client
    echo '<pre>';
    echo " <br> START: ".date("h:i:s");

    $this->settings = $settings;
    $this->validAuthorities = $this->settings->periodAuthorities;
    $this->dataFile = $this->settings->environment->periodsDataFile;
    $elasticEnv = $this->settings->elasticsearchEnv->{$this->settings->environment->elasticsearchEnv};

    $this->client = ClientBuilder::create()->setHosts([$elasticEnv->periodHost])->build();
    $this->createIndex($elasticEnv->periodIndex);
    $this->action();

    echo " <br> COMPLETED:".date("H:i:s");
    echo " <br><br> Updated: ".$elasticEnv->periodHost.'/'.$elasticEnv->periodIndex;
    echo " <br> Numbers of records added: ".$this->recordCount;
    echo " <br> Data source: ".$this->dataFile;
    echo " <br><br> Added authorities:<br><br>";
    print_r( $this->validAuthorities);
    echo '</pre>';

  }

  /**
   * Main function
   */
  private function action() {

    $inData = file_get_contents($this->dataFile);
    $json = json_decode($inData, true);

    forEach($this->validAuthorities as $authorityId=>$authorityUri) {

      $periods = &$json['authorities'][$authorityId]['periods'];
      $currentAuthority = &$json['authorities'][$authorityId];
      if(isset($periods)) {
        forEach($periods as $period) {
          $doc = [
            'authority' => [
              'citation' => $this->getAttribute('citation', $currentAuthority['source']),
              'title' => $this->getAttribute('title', $currentAuthority['source']),
              'url' => $this->getAttribute('url', $currentAuthority['source']),
              //'yearPublished' => $this->getAttribute('yearPublished', $authority['source']),
              'creator' => $this->getAttribute('creators', $currentAuthority['source'],true),
              'contributor' => $this->getAttribute('contributors', $currentAuthority['source'],true),
              'authorityType' => $this->getAttribute('type', $currentAuthority),
              'id' => $this->getAttribute('id', $currentAuthority),
              'editorialNote' => $this->getAttribute('editorialNote', $currentAuthority),
            ],
            'periodType' => $this->getAttribute('type', $period),
            'language' => $this->getAttribute('language', $period),
            'languageTag' => $this->getAttribute('languageTag', $period),
            'id' => $this->getAttribute('id', $period),
            'label' => $this->getAttribute('label', $period),
            //'localizedLabels' => $this->getLocalizedLabel($period),
            'localizedLabels' => $this->getLocalizedLabel($period),
            'spatialCoverage' => $this->getSpatialCoverage($period),
            'spatialCoverageDescription' => $this->getAttribute('spatialCoverageDescription', $period),
            'note' => $this->getAttribute('note', $period),
            'start' => $this->getStartStop('start', $period),
            'stop' => $this->getStartStop('stop', $period),

          ];

          if( $this->getAttribute('yearPublished', $currentAuthority['source']) ) {
            $doc['authority']['yearPublished'] = $this->getAttribute( 'yearPublished', $currentAuthority['source'] );
          }

          $params['index'] = $this->index;
          $params['id'] = $period['id'];
          $params['body'] = $doc;
          $this->client->index($params);
          $this->recordCount++;
        }
      }

    }

  }

  private function getAttribute($attr, &$arr, $isObj=false) {
    if(array_key_exists($attr, $arr)) {
      $r = @is_null($arr[$attr])?'':$arr[$attr];
      if($isObj) {
        if(!$r) {
          return [];
        }
      }
      return $r;
    } else {
      return [];
    }
  }


  private function getStartStop($attr, &$arr) {
    $labels = [];
    if( isset($arr[$attr]) ) {
        if( isset($arr[$attr]['in']['year']) ) {
          $labels['year'] = $arr[$attr]['in']['year'];
        }
        if( isset($arr[$attr]['in']['earliestYear']) ) {
          $labels['earliestYear'] = $arr[$attr]['in']['earliestYear'];
        }
        if( isset($arr[$attr]['in']['latestYear']) ) {
          $labels['latestYear'] = $arr[$attr]['in']['latestYear'];
        }
        if( isset($arr[$attr]['label']) ) {
          $labels['label'] = $arr[$attr]['label'];
        }
        return [$labels];
    }
    return $labels;
  }


  private function getLocalizedLabel(&$arr) {
    $labels = [];
    if( isset($arr['localizedLabels']) ) {
      foreach($arr['localizedLabels'] as $key=>$value) {
        $labels[] = [
          'language' => $key,
          'label' => $value[0]
        ];
      }
    }
    return $labels;
  }

  private function getSpatialCoverage(&$arr) {
    $labels = [];
    if( isset($arr['spatialCoverage']) ) {
      foreach($arr['spatialCoverage'] as $key=>$value) {
        $labels = [
          'id' => $value['id'],
          'label' => $value['label'],
          //'geopoint' => !isset($value['id'])?:$this->getGeopoint($value['id'])
        ];

      }
    }
    return $labels;
  }

  private function getGeopoint( $url ) {

    // Get geopoints for spatial data
    $pos = strrpos($url, '/');
    $id = $pos === false ? $url : substr($url, $pos + 1);
    $url = "https://www.wikidata.org/wiki/Special:EntityData/".$id;
    // Alternative - Query claims directlly with:
    //$url = 'https://www.wikidata.org/w/api.php?action=wbgetentities&props=claims&ids='.$id.'&format=json';

    $headers = array(
      "Accept: application/json",
    );

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);

    //for debug only!
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

    $resp = curl_exec($curl);
    curl_close($curl);

    $geopoint = [];
    if($resp) {
      $json = json_decode($resp, true);
      $geodata = $json['entities'][$id]['claims']['P625'][0]['mainsnak']['datavalue']['value'];
      if(isset($geodata)) {
        $geopoint = ['lat' => $geodata['latitude'], 'lon' => $geodata['longitude']];
      }
    }
    return $geopoint;
  }

    /**
   * Create an index in elastic.
   */
  private function createIndex(string $index) {

    $inData = file_get_contents(__DIR__."/periodo-mapping.json");
    $mapping = json_decode($inData, true);
    try {
      $this->client->indices()->delete(['index' => $index]);
    } catch (Missing404Exception $e) {

      echo '<br>  NOTICE - Created New Period Index: ' . $index ;


    } catch (Exception $e) {
      // index doesnt' exist
      // TODO: Do some error handling
      echo '  <br> ERROR: ' . $e->getMessage();
      exit;
    }

    try {
      $this->client->indices()->create([
        'index' => $index,
        'body' => $mapping,
      ]);
    } catch (Exception $e) {
      echo ' <br> FAILED TO CREATE INDEX: ' . $e->getMessage();
      // TODO: Do some error handling
    }
  }

}

