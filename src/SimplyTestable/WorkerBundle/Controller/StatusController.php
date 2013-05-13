<?php

namespace SimplyTestable\WorkerBundle\Controller;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use SimplyTestable\WorkerBundle\Services\HttpClientService;

class StatusController extends BaseController
{   
    
    public function indexAction()
    {        
        $status = array();        
        $thisWorker = $this->getWorkerService()->get();
        
        $status['hostname'] = $thisWorker->getHostname();
        $status['state'] = $thisWorker->getPublicSerializedState();
        $status['version'] = $this->getLatestGitHash();
        
        if ($this->getHttpClientService()->hasMemcacheCache()) {            
            $status['http_cache_stats'] = $this->getHttpCacheStats();
        }
        
        return $this->sendResponse($status); 
    }
    
    private function getHttpCacheStats() {
        $httpCacheStats = $this->getHttpClientService()->getMemcacheCache()->getStats();
        
        $hitsToMissesRatio = 0;
        
        if ($httpCacheStats['hits'] > 0 && $httpCacheStats['misses'] == 0) {
            $hitsToMissesRatio = 1;
        }
        
        if ($httpCacheStats['hits'] > 0 && $httpCacheStats['misses'] > 0) {
            $hitsPlusMisses = $httpCacheStats['hits'] + $httpCacheStats['misses'];            
            $hitsToMissesRatio = round($httpCacheStats['hits'] / $hitsPlusMisses, 2);
        }
        
        $httpCacheStats['hits-to-misses-ratio'] = $hitsToMissesRatio;
        
        return $httpCacheStats;
    }
    
    
    private function getLatestGitHash() {
        return trim(shell_exec("git log | head -1 | awk {'print $2;'}"));
    }
    
    
    /**
     *
     * @return HttpClientService
     */
    private function getHttpClientService() {
        return $this->container->get('simplytestable.services.httpclientservice');
    }    
    
    
    /**
     *
     * @return WorkerService
     */
    private function getWorkerService() {
        return $this->container->get('simplytestable.services.workerservice');
    }
}
