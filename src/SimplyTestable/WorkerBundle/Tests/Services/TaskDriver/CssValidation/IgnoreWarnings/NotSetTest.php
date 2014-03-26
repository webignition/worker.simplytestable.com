<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\IgnoreWarnings;

class NotSetTest extends IgnoreWarningsTest {
    
    protected function getExpectedWarningCount() {
        return 3;
    }

    protected function getTaskParameters() {
        return array();
    }

}
