<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\DomainsToIgnore;

class IgnoreTwoDomainsOfThreeTest extends DomainsToIgnoreTest {
    
    
    protected function getExpectedErrorCount() {
        return 1;
    }

    protected function getTaskParameters() {
        return array(
            'domains-to-ignore' => array(
                'one.cdn.example.com',
                'two.cdn.example.com'
            )              
        );
    }

}
