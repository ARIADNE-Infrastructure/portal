<?php

namespace Elastic;

use Elastic\Query;
use Elastic\Utils;
use Location\Coordinate; // https://github.com/mjaschen/phpgeo
use Location\Polygon;
use \geoPHP\geoPHP; // https://geophp.net/

/**
 * Handle geo specific tasks
 */
class GeoUtils {
  private const TOP_LEFT = 315; // Bearing Degrees - North West
  private const BOTTOM_RIGHT = 135; // Bearing Degrees - South East
  private const POINT_EXTEND_KM = 2;

  /**
   * Calculate new point. Starting point lat/lon, with distance and bearing.
   */
  private static function getExtendedGeoPoint ($lat, $lon, $bearing, $distanceKilometers) {
    $earthRadius = 6371;
    $lat1 = deg2rad($lat);
    $lon1 = deg2rad($lon);
    $bearing = deg2rad($bearing);
    $lat2 = asin(sin($lat1) * cos($distanceKilometers / $earthRadius) + cos($lat1) * sin($distanceKilometers / $earthRadius) * cos($bearing));
    $lon2 = $lon1 + atan2(sin($bearing) * sin($distanceKilometers / $earthRadius) * cos($lat1), cos($distanceKilometers / $earthRadius) - sin($lat1) * sin($lat2));
    return [
      'lat' => rad2deg($lat2),
      'lon' => rad2deg($lon2),
    ];
  }

  /** Alias */
  private static function getTopLeftCorner ($lat, $lon) {
    return self::getExtendedGeoPoint($lat, $lon, self::TOP_LEFT, self::POINT_EXTEND_KM);
  }

  /** Alias */
  private static function getBottomRightCorner ($lat, $lon) {
    return self::getExtendedGeoPoint($lat, $lon, self::BOTTOM_RIGHT, self::POINT_EXTEND_KM);
  }

  /**
   * Extract all geoshapes with it's centroid within given boundingbox. Geopoints are default included.
   */
  private static function filterGeoshapeCentroidWithinBoundingbox ($resources, $bbox) {
    $filteredResources = [];
    foreach ($resources as $id => $resource) {
      $resource['_source'] = Utils::splitLanguages($resource['_source'], Query::instance()->getDefaultLanguage());
      $hasCentroidWithin = true;
      foreach ($resource['_source']['spatial'] as $idx => $spatial) {
        if (isset($spatial['polygon']) || isset($spatial['boundingbox'])) {
          $geoShape = !empty($spatial['polygon']) ? $spatial['polygon'] : $spatial['boundingbox'];
          $geoShape = geoPHP::load($geoShape, 'wkt');
          try {
            // Get geo shape center
            $geoShapeCenter = new Coordinate($geoShape->getCentroid()->getY(), $geoShape->getCentroid()->getX());
          } catch (\InvalidArgumentException $e) {
            break;
          }
          // Check if geoshape center is inside bbox.
          // If one of resources spatials are outside, filter out whole resource
          if (!$bbox->contains($geoShapeCenter)) {
            $hasCentroidWithin = false;
            break;
          }
        }
      }
      if ($hasCentroidWithin) {
        $filteredResources[] = $resource;
      }
    }
    return $filteredResources;
  }

  /**
   * Get spatial nearby from given record
   */
  public static function getNearbyResources ($record) {
    $latLon = null;
    if (!empty($record['spatial'])) {
      foreach ($record['spatial'] as $item) {
        if (!empty($item['geopoint']) && !empty($item['geopoint']['lat']) && !empty($item['geopoint']['lon'])) {
          $latLon['lat'] = $item['geopoint']['lat'];
          $latLon['lon'] = $item['geopoint']['lon'];
          break;
        } elseif (!empty($item['polygon']) || !empty($item['boundingbox'])) {
          $geoShape = !empty($item['polygon']) ? $item['polygon'] : $item['boundingbox'];
          $geoShape = geoPHP::load( $geoShape ,'wkt');
          $latLon['lat'] = $geoShape->getCentroid()->getY();
          $latLon['lon'] = $geoShape->getCentroid()->getX();
          break;
        }
      }
    }
    $topLeftCorner = self::getTopLeftCorner($latLon['lat'] ?? 0, $latLon['lon'] ?? 0);
    $bottomRightCorner = self::getBottomRightCorner($latLon['lat'] ?? 0, $latLon['lon'] ?? 0);
    // Geopoints
    $boundingBoxFilters[] = [
      'nested' => [
        'path' => 'spatial',
        'query' => [
          'geo_bounding_box' => [
            'spatial.geopoint' => [
              'top_left' => [
                'lat' => $topLeftCorner['lat'],
                'lon' => $topLeftCorner['lon'],
              ],
              'bottom_right' => [
                'lat' => $bottomRightCorner['lat'],
                'lon' => $bottomRightCorner['lon']
              ]
            ]
          ]
        ]
      ]
    ];
    // Possible Geo shapes nested query
    // topLeft.lon, topLeft.lat, bottomRight.lon,bottomRight.lat
    $possibleGeoShapes = ['polygon', 'boundingbox'];
    foreach ($possibleGeoShapes as $geoShape) {
      $boundingBoxFilters[] = [
        'nested' => [
          'path' => 'spatial',
          'query' => [
            'geo_shape' => [
              'spatial.'.$geoShape => [
                'shape' => [
                  'type' => 'envelope',
                  'relation' => 'within',
                  'coordinates' => [
                    [
                      $topLeftCorner['lon'],
                      $topLeftCorner['lat']
                    ],
                    [
                      $bottomRightCorner['lon'],
                      $bottomRightCorner['lat']
                    ]
                  ]
                ]
              ]
            ]
          ]
        ]
      ];
    }
    $query['size'] = 200;
    $query['_source'] = ['title', 'spatial', 'language'];
    $query['query']['bool']['filter'][] = [
      'bool' => [
        'should' => $boundingBoxFilters
      ]
    ];
    // Exclude own
    $query['query']['bool']['must_not']['term']['_id'] = $record['id'];
    $nearbyResources = Query::instance()->elasticDoSearch($query);
    // Construct box
    $bbox = new Polygon();
    $bbox->addPoint(new Coordinate($topLeftCorner['lat'], $topLeftCorner['lon']));
    $bbox->addPoint(new Coordinate($topLeftCorner['lat'], $bottomRightCorner['lon']));
    $bbox->addPoint(new Coordinate($bottomRightCorner['lat'], $bottomRightCorner['lon']));
    $bbox->addPoint(new Coordinate($bottomRightCorner['lat'], $topLeftCorner['lon']));
    $filteredResources = self::filterGeoshapeCentroidWithinBoundingbox($nearbyResources['hits']['hits'], $bbox);
    // Cleanup
    $nearBy = [];
    foreach ($filteredResources as $resource) {
      if (!empty($resource['_id'])) {
        $nearBy[] = [
          'title' => $resource['_source']['title'] ?? '',
          'spatial' => $resource['_source']['spatial'] ?? '',
          'id' => $resource['_id'],
        ];
      }
    }
    return $nearBy;
  }
}
