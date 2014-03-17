<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\LinkIntegrity\CookieParameters;

class PathTest extends FirstRequestHasCookiesSecondRequestDoesNotTest {    
    
    protected function getExpectedCookies() {
        return array(
            array(
                'domain' => '.example.com',
                'path' => '/foo/bar',
                'name' => 'key1',
                'value' => 'value1'
            ),
            array(
                'domain' => '.example.com',
                'path' => '/foo/bar',
                'name' => 'key2',
                'value' => 'value2'
            )        
        );        
    }    
    
}
