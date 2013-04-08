<?php

namespace SimplyTestable\WorkerBundle\Tests\Guzzle;

use SimplyTestable\WorkerBundle\Tests\BaseSimplyTestableTestCase;

class MemcacheServiceTest extends BaseSimplyTestableTestCase {
    
    /**
     * @group standard
     */    
    public function testHasMemcache() {
        $this->assertTrue(class_exists('\Memcache'));
    }     
    
    /**
     * @group standard
     */    
    public function testGetMemcacheService() {
        $this->assertTrue($this->getMemcacheService() instanceof \Simplytestable\WorkerBundle\Services\MemcacheService);
    }
    
    /**
     * @group standard
     */    
    public function testGetMemcache() {
        $this->assertTrue($this->getMemcacheService()->get() instanceof \Memcache);
    }    

}
