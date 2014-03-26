<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\IgnoreWarnings;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\StandardCssValidationTaskDriverTest;

abstract class IgnoreWarningsTest extends StandardCssValidationTaskDriverTest {
    
    protected function getFixtureTestName() {
        return null;
    }
    
    protected function getFixtureUpLevelsCount() {
        return 1;
    }    
    
    protected function getExpectedErrorCount() {
        return 0;
    }
}
