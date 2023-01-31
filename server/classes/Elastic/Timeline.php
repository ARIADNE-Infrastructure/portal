<?php

namespace Elastic;

class Timeline {
  /**
   * Creates an elasticsearch aggregation query. Each of the intervals derived
   * from $range is taken separately. It
   * is split in a linear manner so that buckets spanning the same amount of
   * years will get created. For every interval the number of buckets created is the
   * same but the number of years a bucket spans varies from interval to interval.
   */
  public static function prepareRangeBucketsAggregation($range) {
    if (empty($range) || count($range) < 2) {
      $c_year = date("Y");
      $initialRange = [-1000000, -100000, -10000, -1000, 0, 1000, 1250, 1500, 1750];
      array_push($initialRange, $c_year);
      $range = $initialRange;
    } else {
      for ($k = 0, $len = count($range); $k < $len; $k++) {
        $range[$k] = intval($range[$k]);
      }
    }

    $nrIntervals = count($range) - 1;
    $nrDefaultBuckets = 50;
    $nrBucketsPerInterval = $nrDefaultBuckets;
    if ($nrIntervals > 1) {
      $nrBucketsPerInterval = intval(floor($nrDefaultBuckets / $nrIntervals));
    }

    $ranges = [];
    for ($i = 0; $i < $nrIntervals; $i++) {
      // add intervals to last range to ensure that nrDefaultBuckets are returned
      if ($i == $nrIntervals - 1) {
        $nrBucketsPerInterval += $nrDefaultBuckets % $nrIntervals;
      }
      $startYear = $range[$i];
      $endYear = $range[$i + 1];
      $diff = $endYear - $startYear;
      $delta = $diff / $nrBucketsPerInterval;
      $currentStartYear = $startYear;
      for ($j = 0; $j < $nrBucketsPerInterval; $j++) {
        $rangeStartYear = intval(round($currentStartYear));
        $rangeEndYear = intval(round($currentStartYear + $delta));
        $ranges[$rangeStartYear . ':' . $rangeEndYear] = [
          'bool' => [
            'must' => [
              [
                'range' => [
                  'temporal.until' => [
                    'gte' => '' . $rangeStartYear
                  ]
                ]
              ],
              [
                'range' => [
                  'temporal.from' => [
                    'lte' => '' . $rangeEndYear
                  ]
                ]
              ]
            ]
          ]
        ];
        $currentStartYear = $currentStartYear + $delta;
      }
    }

    return [
      'nested' => [
        'path' => 'temporal'
      ],
      'aggs' => [
        'range_agg' => [
          'filters' => [
            'filters' => $ranges
          ]
        ]
      ]
    ];
  }

  /**
   * Get all resources where 'from' OR 'until' is INSIDE range
   */
  public static function buildRangeIntersectingInnerQuery($range) {
    $dateRange = null;
    if ($range) {
      $range = explode(',', $range);
      if (sizeof($range) > 1) {
        $start = intval($range[0]);
        $stop = intval($range[1]);
        $intersectingCombinations = [
          [
            [
              'range' => [
                'temporal.until' => [
                  'gte' => $start
                ]
              ]
            ],
            [
              'range' => [
                'temporal.until' => [
                  'lte' => $stop
                ]
              ]
            ]
          ],
          [
            [
              'range' => [
                'temporal.from' => [
                  'gte' => $start
                ]
              ]
            ],
            [
              'range' => [
                'temporal.until' => [
                  'lte' => $stop
                ]
              ]
            ]
          ],
          [
            [
              'range' => [
                'temporal.from' => [
                  'gte' => $start
                ]
              ]
            ],
            [
              'range' => [
                'temporal.from' => [
                  'lte' => $stop
                ]
              ]
            ]
          ],
          [
            [
              'range' => [
                'temporal.from' => [
                  'lte' => $stop
                ]
              ]
            ],
            [
              'range' => [
                'temporal.until' => [
                  'gte' => $start
                ]
              ]
            ]
          ]
        ];

        foreach($intersectingCombinations as $combo) {
          $dateRange[] = [
            'nested' => [
              'path' => 'temporal',
              'query' => [
                'bool' => [
                  'must' => $combo
                ]
              ]
            ]
          ];
        }
      }
    }
    return $dateRange;
  }
}
