<?php

namespace SimplyTestable\WorkerBundle\Tests\Guzzle;

use SimplyTestable\WorkerBundle\Tests\BaseSimplyTestableTestCase;

class MemcacheServiceTest extends BaseSimplyTestableTestCase {
    
    /**
     * @group standard
     */    
    public function testGetMemcacheService() {
        $this->assertInstanceOf('\Simplytestable\WorkerBundle\Services\MemcacheService', $this->getMemcacheService());
    }
    
    /**
     * @group standard
     */    
    public function testGetMemcache() {
        $this->assertInstanceOf('\Memcache', $this->getMemcacheService()->get());
    }    

}
