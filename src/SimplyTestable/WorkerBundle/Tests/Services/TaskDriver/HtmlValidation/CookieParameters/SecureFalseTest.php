<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\HtmlValidation\CookieParameters;

class SecureFalseTest extends CookieParametersTest {   
    
    protected function getTaskUrl() {
        return 'http://example.com/';
    }
    

    protected function getExpectedRequestsOnWhichCookiesShouldBeSet() {
        return array();
    }

    protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet() {
        $requests = array();
        
        foreach ($this->getHttpClientService()->getHistory()->getAll() as $httpTransaction) {
            $requests[] = $httpTransaction['request'];
        }
        
        return $requests;        
    }    
}
