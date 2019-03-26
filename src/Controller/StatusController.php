<?php

namespace App\Controller;

use App\Model\HttpCacheStats;
use App\Services\ApplicationConfiguration;
use App\Services\ApplicationState;
use Doctrine\Common\Cache\MemcachedCache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class StatusController extends AbstractController
{
    public function indexAction(
        ApplicationConfiguration $applicationConfiguration,
        ApplicationState $applicationState,
        MemcachedCache $memcachedCache
    ): JsonResponse {
        $status = [
            'hostname' => $applicationConfiguration->getHostname(),
            'state' => $applicationState->get(),
            'version' => $this->getLatestGitHash(),
        ];

        $httpCacheStats = new HttpCacheStats($memcachedCache->getStats());
        $status['http_cache_stats'] = $httpCacheStats->getFormattedStats();

        return new JsonResponse($status);
    }

    private function getLatestGitHash(): string
    {
        return trim(shell_exec("git log | head -1 | awk {'print $2;'}"));
    }
}
