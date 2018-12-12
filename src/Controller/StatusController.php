<?php

namespace App\Controller;

use App\Model\HttpCacheStats;
use App\Services\ApplicationState;
use App\Services\HttpCache;
use App\Services\WorkerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class StatusController extends AbstractController
{
    public function indexAction(
        WorkerService $workerService,
        ApplicationState $applicationState,
        HttpCache $httpCache
    ): JsonResponse {
        $status = [
            'hostname' => $workerService->getHostname(),
            'state' => $applicationState->get(),
            'version' => $this->getLatestGitHash(),
        ];

        if ($httpCache->has()) {
            $httpCacheStats = new HttpCacheStats($httpCache->get()->getStats());
            $status['http_cache_stats'] = $httpCacheStats->getFormattedStats();
        }

        return new JsonResponse($status);
    }

    private function getLatestGitHash(): string
    {
        return trim(shell_exec("git log | head -1 | awk {'print $2;'}"));
    }
}
