<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\DomainsToIgnore;

class NotsetTest extends DomainsToIgnoreTest {
    
    
    protected function getExpectedErrorCount() {
        return 3;
    }

    protected function getTaskParameters() {
        return array();
    }

}
