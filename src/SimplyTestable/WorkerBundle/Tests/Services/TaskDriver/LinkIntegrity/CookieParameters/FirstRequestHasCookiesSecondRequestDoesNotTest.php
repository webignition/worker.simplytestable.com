<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\LinkIntegrity\CookieParameters;

abstract class FirstRequestHasCookiesSecondRequestDoesNotTest extends CookieParametersTest {      
    
    protected function getExpectedRequestsOnWhichCookiesShouldBeSet() {                
        $requests = $this->getAllRequests();
        return $requests[0];
    }  
    
    protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet() {
        $requests = $this->getAllRequests();
        return $requests[1];
    }     
    
    
    private function getAllRequests() {
        $requests = array();
        
        foreach ($this->getHttpClientService()->getHistory()->getAll() as $httpTransaction) {
            $requests[] = $httpTransaction['request'];
        }
        
        return $requests;        
    }
}
