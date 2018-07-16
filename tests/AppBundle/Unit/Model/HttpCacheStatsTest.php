<?php

namespace Tests\AppBundle\Unit\Model;

use App\Model\HttpCacheStats;

class HttpCacheStatsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider calculateHitsToMissesRatioDataProvider
     *
     * @param array $httpCacheStats
     * @param float|int $expectedHitsToMissesRatio
     */
    public function testCalculateHitsToMissesRatio($httpCacheStats, $expectedHitsToMissesRatio)
    {
        $httpCacheStatsModel = new HttpCacheStats($httpCacheStats);

        $this->assertEquals(
            $expectedHitsToMissesRatio,
            $httpCacheStatsModel->getFormattedStats()[HttpCacheStats::KEY_HITS_TO_MISSES_RATIO]
        );
    }

    /**
     * @return array
     */
    public function calculateHitsToMissesRatioDataProvider()
    {
        return [
            'hits-to-misses-ratio: 0' => [
                'httpCacheStats' => [
                    'hits' => 0,
                    'misses' => 1,
                ],
                'expectedHitsToMissesRatio' => 0,
            ],
            'hits-to-misses-ratio: 1' => [
                'httpCacheStats' => [
                    'hits' => 1,
                    'misses' => 0,
                ],
                'expectedHitsToMissesRatio' => 1,
            ],
            'hits-to-misses-ratio: 0.5' => [
                'httpCacheStats' => [
                    'hits' => 1,
                    'misses' => 1,
                ],
                'expectedHitsToMissesRatio' => 0.5,
            ],
        ];
    }
}
