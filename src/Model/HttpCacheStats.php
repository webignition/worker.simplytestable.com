<?php
namespace App\Model;

class HttpCacheStats
{
    const KEY_HITS_TO_MISSES_RATIO = 'hits-to-misses-ratio';
    const KEY_HITS = 'hits';
    const KEY_MISSES = 'misses';

    private $httpCacheStats = [];

    public function __construct(?array $httpCacheStats)
    {
        $this->httpCacheStats = $httpCacheStats ?? [];
    }


    public function getFormattedStats(): array
    {
        return array_merge($this->httpCacheStats, [
            self::KEY_HITS_TO_MISSES_RATIO => $this->calculateHitsToMissesRatio(),
        ]);
    }

    /**
     * @return float|int
     */
    private function calculateHitsToMissesRatio()
    {
        $hits = $this->httpCacheStats[self::KEY_HITS] ?? 0;
        $misses = $this->httpCacheStats[self::KEY_MISSES] ?? 0;

        if ($hits > 0 && $misses === 0) {
            return 1;
        }

        if ($hits > 0 && $misses > 0) {
            return round($hits / ($hits + $misses), 2);
        }

        return 0;
    }
}
