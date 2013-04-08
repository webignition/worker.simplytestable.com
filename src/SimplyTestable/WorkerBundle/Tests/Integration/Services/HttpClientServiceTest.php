<?php

namespace SimplyTestable\WorkerBundle\Tests\Integration\Services;

use SimplyTestable\WorkerBundle\Tests\BaseSimplyTestableTestCase;
use Doctrine\Common\Cache\MemcacheCache;

class HttpClientServiceTest extends BaseSimplyTestableTestCase {
    
    /**
     * @group integration
     */    
    public function testMemcacheCache() {               
        $memcacheCache = new MemcacheCache();
        $memcacheCache->setMemcache($this->getMemcacheService()->get());
        $memcacheCache->deleteAll();
        
        $request1 = $this->getHttpClientService()->getRequest('http://webignition.net');        
        $request1TimeBefore = microtime(true);
        $request1->send();
        $request1Duration = microtime(true) - $request1TimeBefore;
        
        $request2 = $this->getHttpClientService()->getRequest('http://webignition.net');   
        $request2TimeBefore = microtime(true);
        $request2->send();
        $request2Duration = microtime(true) - $request2TimeBefore;
        
        $this->assertTrue(($request1Duration / $request2Duration) > 10);
    }   

}
