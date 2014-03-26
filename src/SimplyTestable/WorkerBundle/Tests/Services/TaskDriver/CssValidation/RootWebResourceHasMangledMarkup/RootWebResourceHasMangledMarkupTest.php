<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\RootWebResourceHasMangledMarkup;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\StandardCssValidationTaskDriverTest;

class RootWebResourceHasMangledMarkupTest extends StandardCssValidationTaskDriverTest {    
    
    protected function getFixtureTestName() {
        return null;
    }
    
    protected function getFixtureUpLevelsCount() {
        return 1;
    }    
    
    protected function getTaskParameters() {
        return array();
    }

    protected function getExpectedErrorCount() {
        return 0;
    }

    protected function getExpectedWarningCount() {
        return 0;
    }

}
