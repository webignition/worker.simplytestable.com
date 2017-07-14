<?php

namespace SimplyTestable\WorkerBundle\Controller;

use SimplyTestable\WorkerBundle\Model\HttpCacheStats;
use SimplyTestable\WorkerBundle\Services\HttpCache;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class StatusController extends AbstractController
{
    /**
     * @param WorkerService $workerService
     * @param HttpCache $httpCache
     *
     * @return JsonResponse
     */
    public function indexAction(WorkerService $workerService, HttpCache $httpCache)
    {
        $status = array();
        $thisWorker = $workerService->get();

        $status['hostname'] = $thisWorker->getHostname();
        $status['state'] = $thisWorker->getState()->getName();
        $status['version'] = $this->getLatestGitHash();

        if ($httpCache->has()) {
            $httpCacheStats = new HttpCacheStats($httpCache->get()->getStats());
            $status['http_cache_stats'] = $httpCacheStats->getFormattedStats();
        }

        return new JsonResponse($status);
    }

    /**
     * @return string
     */
    private function getLatestGitHash()
    {
        return trim(shell_exec("git log | head -1 | awk {'print $2;'}"));
    }
}
