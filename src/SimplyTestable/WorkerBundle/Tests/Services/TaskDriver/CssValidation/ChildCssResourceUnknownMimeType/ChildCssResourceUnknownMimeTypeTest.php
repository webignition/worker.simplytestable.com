<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\ChildCssResourceUnknownMimeType;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\StandardCssValidationTaskDriverTest;

class ChildCssResourceUnknownMimeTypeTest extends StandardCssValidationTaskDriverTest {    
    
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
        return 1;
    }

    protected function getExpectedWarningCount() {
        return 0;
    }    
    
    public function testOutputMessage() {
        $decodedTaskOutput = json_decode($this->task->getOutput()->getOutput());        
        $this->assertEquals('invalid-content-type:invalid/made-it-up', $decodedTaskOutput[0]->message);        
    }   

}
