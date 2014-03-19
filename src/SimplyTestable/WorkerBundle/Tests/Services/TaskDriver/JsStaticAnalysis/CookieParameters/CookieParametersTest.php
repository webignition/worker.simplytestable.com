<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\CookieParameters;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\TaskDriverTest;

abstract class CookieParametersTest extends TaskDriverTest {
    
    protected $task;
    
    abstract protected function getExpectedCookies();
    abstract protected function getExpectedRequestsOnWhichCookiesShouldBeSet();
    abstract protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet();
    
    public function setUp() {
        parent::setUp();
        $this->task = $this->getTask('http://example.com/', array(
            'cookies' => $this->getExpectedCookies()
        ));
        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath('/HttpResponses')));
        
        $this->container->get('simplytestable.services.nodeJsLintWrapperService')->setValidatorRawOutput(
            file_get_contents($this->getFixturesDataPath('../NodeJslintResponse/1'))
        );
        
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
        
        foreach ($this->getExpectedCookies() as $cookie) {
            $nameValueArray[$cookie['name']] = $cookie['value'];
        }
        
        return $nameValueArray;
    }
    
}
