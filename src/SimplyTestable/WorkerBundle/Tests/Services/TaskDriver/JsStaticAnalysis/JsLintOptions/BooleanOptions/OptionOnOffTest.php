<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\JsLintOptions\BooleanOptions;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\TaskDriverTest;

abstract class OptionOnOffTest extends TaskDriverTest {
    
    protected function offTest($className) {          
        $this->withValueTest($className, '0');     
    }    
    
    protected function onTest($className) {        
        $this->withValueTest($className, '1');    
    }
    
    private function withValueTest($className, $value) {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(null) . '/HttpResponses'));        
        
        $task = $this->getTask('http://example.com/', array(
            'jslint-option-'.$this->getOptionNameFromClassName($className) => $value
        ));
        
        $expectedErrorCount = ($value === '1') ? 0 : 1;
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));     
        $this->assertEquals($expectedErrorCount, $task->getOutput()->getErrorCount());                   
    }
    
    private function getOptionNameFromClassName($className) {
        $classNameParts = explode('\\', $className);        
        return strtolower(str_replace('Test', '', $classNameParts[count($classNameParts) - 1]));
    }


}
