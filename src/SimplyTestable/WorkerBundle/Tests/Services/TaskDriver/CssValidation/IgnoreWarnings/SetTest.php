<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\IgnoreWarnings;

class SetTest extends IgnoreWarningsTest {
    
    protected function getExpectedWarningCount() {
        return 0;
    }

    protected function getTaskParameters() {
        return array(
            'ignore-warnings' => true            
        );
    }

}
