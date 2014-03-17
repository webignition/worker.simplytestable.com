<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\UrlDiscovery\CookieParameters;

class SecureTrueTest extends CookieParametersTest {   
    
    protected function getTaskUrl() {
        return 'https://example.com/';
    }
    

    protected function getExpectedRequestsOnWhichCookiesShouldBeSet() {
        $requests = array();
        
        foreach ($this->getHttpClientService()->getHistory()->getAll() as $httpTransaction) {
            $requests[] = $httpTransaction['request'];
        }
        
        return $requests;          
    }

    protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet() {        
        return array();      
    }    
}
