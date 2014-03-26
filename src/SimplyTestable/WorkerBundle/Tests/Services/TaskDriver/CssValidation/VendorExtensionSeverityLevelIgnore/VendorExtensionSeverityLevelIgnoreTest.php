<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\VendorExtensionSeverityLevelIgnore;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\StandardCssValidationTaskDriverTest;

class VendorExtensionSeverityLevelIgnoreTest extends StandardCssValidationTaskDriverTest {    
    
    protected function getFixtureTestName() {
        return null;
    }
    
    protected function getFixtureUpLevelsCount() {
        return 1;
    }    
    
    protected function getTaskParameters() {
        return array(
            'vendor-extensions' => 'ignore'           
        );
    }

    protected function getExpectedErrorCount() {
        return 0;
    }

    protected function getExpectedWarningCount() {
        return 0;
    }

}
