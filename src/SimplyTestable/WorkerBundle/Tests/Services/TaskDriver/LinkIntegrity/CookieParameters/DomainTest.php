<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\LinkIntegrity\CookieParameters;

class DomainTest extends FirstRequestHasCookiesSecondRequestDoesNotTest {    
    
    protected function getExpectedCookies() {
        return array(
            array(
                'domain' => '.example.com',
                'name' => 'key1',
                'value' => 'value1'
            ),
            array(
                'domain' => '.example.com',
                'name' => 'key2',
                'value' => 'value2'
            )        
        );        
    }    
    
}
