<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\UrlDiscovery\CookieParameters;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\UrlDiscovery\TaskDriverTest;

abstract class CookieParametersTest extends TaskDriverTest {
    
    protected $task;
    
    private $cookies = array(
        array(
            'domain' => '.example.com',
            'secure' => true,
            'name' => 'key1',
            'value' => 'value1'
        ),
        array(
            'domain' => '.example.com',
            'secure' => true,
            'name' => 'key2',
            'value' => 'value2'
        )        
    );  

    abstract protected function getTaskUrl();
    abstract protected function getExpectedRequestsOnWhichCookiesShouldBeSet();
    abstract protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet();
    
    public function setUp() {
        parent::setUp();
        $this->task = $this->getTask($this->getTaskUrl(), array(
            'cookies' => $this->cookies
        ));
        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath('/HttpResponses')));
        
        $this->getTaskService()->perform($this->task);
    }
    
    public function testCookiesAreSetOnExpectedRequests() {
        foreach ($this->getExpectedRequestsOnWhichCookiesShouldBeSet() as $request) {         
            $this->assertEquals($this->getExpectedCookieValues(), $request->getCookies());
        }
    }
    
    public function testCookiesAreNotSetOnExpectedRequests() {  
        foreach ($this->getExpectedRequestsOnWhichCookiesShouldNotBeSet() as $request) {            
            $this->assertEmpty($request->getCookies());
        }
    }
    
    
    /**
     * 
     * @return array
     */
    private function getExpectedCookieValues() {
        $nameValueArray = array();
        
        foreach ($this->cookies as $cookie) {
            $nameValueArray[$cookie['name']] = $cookie['value'];
        }
        
        return $nameValueArray;
    }
    
}