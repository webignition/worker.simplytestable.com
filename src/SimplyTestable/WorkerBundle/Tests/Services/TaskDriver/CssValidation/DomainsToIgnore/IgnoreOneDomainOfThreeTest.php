<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\DomainsToIgnore;

class IgnoreOneDomainOfThreeTest extends DomainsToIgnoreTest {
    
    
    protected function getExpectedErrorCount() {
        return 2;
    }

    protected function getTaskParameters() {
        return array(
            'domains-to-ignore' => array(
                'one.cdn.example.com'
            )              
        );
    }

}
