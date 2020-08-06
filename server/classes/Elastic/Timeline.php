<?php

namespace Elastic;

use Log;
use Exception;

class Timeline {

    const nrDefaultBuckets = 50;

    public static $initialRange = [-1000000,-100000,-10000,-1000,0,1000,1250,1500,1750];
    /**
     * Creates an elasticsearch aggregation query. Each of the intervals derived
     * from $range is taken separately. It
     * is split in a linear manner so that buckets spanning the same amount of
     * years will get created. For every interval the number of buckets created is the
     * same but the number of years a bucket spans varies from interval to interval.
     *
     * @param range {array[string]} Contains years which divide a timespan into sevaral intervals.
     *   $range array with size > 1. Not null.
     *
     * @return Elasticsearch agg partial
     * @throws Exception if illegal argument.
     */
    public static function prepareRangeBucketsAggregation($range) {

        if ($range==null || !(sizeOf($range)>1)) {
            $c_year = date("Y");
            array_push(self::$initialRange, $c_year);
            $range = self::$initialRange;
        } else {
            for ($j = 0, $len = count($range); $j < $len; $j++) {
                $range[$j] = intval($range[$j]);
            }
        }

        $nrIntervals=sizeof($range)-1;
        $nrBucketsPerInterval=self::bucketsPerInterval($nrIntervals);

        $ranges=array();
        for ($i=0;$i<$nrIntervals;$i++) {
            // add intervals to last range to ensure that nrDefaultBuckets are returned
            if ($i == $nrIntervals-1) $nrBucketsPerInterval += self::nrDefaultBuckets % $nrIntervals;
            self::buildAndAddAggRangePartial($ranges,$range[$i],$range[$i+1],$nrBucketsPerInterval);
        }

        return self::aggShell($ranges);
    }


    /**
     * @param $nrIntervals
     * @return int
     */
    private static function bucketsPerInterval($nrIntervals) {
        $bucketsPerInterval=self::nrDefaultBuckets;
        if ($nrIntervals>1) {
            $bucketsPerInterval = intval(
                floor(self::nrDefaultBuckets/$nrIntervals)
            );
        }
        return $bucketsPerInterval;
    }

    private static function aggShell($ranges) {
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
     * @param $ranges {array} range aggregation to items to
     * @param $startYear {string}
     * @param $endYear {string}
     * @param $nrBuckets {int}
     */
    private static function buildAndAddAggRangePartial(&$ranges, $startYear, $endYear, $nrBuckets) {
        $diff = $endYear - $startYear;
        $delta = $diff/$nrBuckets;
        $currentStartYear=$startYear;
        for ($i=0;$i<$nrBuckets;$i++) {
            self::addRange($ranges,$currentStartYear,$currentStartYear+$delta);
            $currentStartYear=$currentStartYear+$delta;
        }
    }


    /**
     * Generates a meaningful key for the range and places
     * a newly generated elasticsearch range agg item to ranges[key]
     *
     * @param $ranges {array} range aggregation to push items to.
     * @param $rangeStartYear {float}
     * @param $rangeEndYear {float}
     */
    private static function addRange(&$ranges,$rangeStartYear,$rangeEndYear) {
        $rangeStartYear= intval(round($rangeStartYear));
        $rangeEndYear= intval(round($rangeEndYear));

        $key=$rangeStartYear.":".$rangeEndYear;
        $ranges[$key]=self::buildRangeQuery(
            $rangeStartYear,
            $rangeEndYear);
    }

    /**
     * ElasticSearch aggregation partial.
     *
     * @param $rangeStartYear
     * @param $rangeEndYear
     * @return array
     */
    public static function buildRangeQuery($rangeStartYear, $rangeEndYear) {
        return
            [
                'bool' => [
                    'must' => [
                        [
                            'range' => [
                                'temporal.until' => [
                                    'gte' =>
                                        "". // type conversion for es to work properly
                                        $rangeStartYear
                                ]
                            ]
                        ],
                        [
                            'range' => [
                                'temporal.from' => [
                                    'lte' =>
                                        "". // type conversion for es to work properly
                                        $rangeEndYear
                                ]
                            ]
                        ]
                    ]
                ]
            ];
    }

}
