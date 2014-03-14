<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\CookieParameters;

class SecureResourceTest extends CookieParametersTest {
    
    protected function getExpectedCookies() {
        return array(
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
    }    

    protected function getExpectedRequestsOnWhichCookiesShouldBeSet() {
        $allRequests = $this->getHttpClientService()->getHistory()->getAll();
        return $allRequests[0]['request'];
    }

    protected function getExpectedRequestsOnWhichCookiesShouldNotBeSet() {        
        $allRequests = $this->getHttpClientService()->getHistory()->getAll();
        return $allRequests[1]['request'];
    }    
}
