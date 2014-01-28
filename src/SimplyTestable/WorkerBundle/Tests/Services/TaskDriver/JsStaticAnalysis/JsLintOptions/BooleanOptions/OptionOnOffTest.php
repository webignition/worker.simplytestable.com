<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\JsLintOptions\BooleanOptions;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\TaskDriverTest;

abstract class OptionOnOffTest extends TaskDriverTest {
    
    public function setUp() {
        parent::setUp();
        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath() . '/HttpResponses'));
        
        $this->container->get('simplytestable.services.nodeJsLintWrapperService')->setValidatorRawOutput(
            file_get_contents($this->getFixturesDataPath($this->getName() . '/NodeJslintResponse/1'))
        );   
    }    
    
    protected function offTest($className) {          
        $this->withValueTest($className, '0');     
    }    
    
    protected function onTest($className) {        
        $this->withValueTest($className, '1');    
    }
    
    private function withValueTest($className, $value) {        
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
