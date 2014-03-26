<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\IgnoreFalseBackgroundImageDataUrlIssues;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\StandardCssValidationTaskDriverTest;

class IgnoreFalseBackgroundImageDataUrlIssuesTest extends StandardCssValidationTaskDriverTest {    
    
    protected function getFixtureTestName() {
        return null;
    }
    
    protected function getFixtureUpLevelsCount() {
        return 1;
    }    
    
    protected function getTaskParameters() {
        return array(
            'ignore-warnings' => true            
        );
    }

    protected function getExpectedErrorCount() {
        return 0;
    }

    protected function getExpectedWarningCount() {
        return 0;
    }

}
