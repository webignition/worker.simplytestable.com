<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\CookieParameters;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\StandardCssValidationTaskDriverTest;

abstract class CookieParametersTest extends StandardCssValidationTaskDriverTest {
    
    protected function getFixtureTestName() {
        return null;
    }
    
    protected function getFixtureUpLevelsCount() {
        return 0;
    }    
    
    abstract protected function getExpectedCookies();
    abstract protected function getExpectedRequestsOnWhichCookiesShouldBeSet();
    abstract protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet();
    
    protected function getTaskParameters() {
        return array(
            'cookies' => $this->getExpectedCookies()
        );
    }
    
    protected function getExpectedErrorCount() {
        return 0;
    }
    
    protected function getExpectedWarningCount() {
        return 0;
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
