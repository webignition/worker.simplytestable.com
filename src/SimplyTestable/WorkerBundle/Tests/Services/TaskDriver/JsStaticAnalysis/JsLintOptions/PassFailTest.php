<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\JsLintOptions;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\TaskDriverTest;

class PassFailTest extends TaskDriverTest {
    
    const NON_FILTERED_ERROR_COUNT = 2;  
    
    public function setUp() {
        parent::setUp();
        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath() . '/HttpResponses'));
        
        $this->container->get('simplytestable.services.nodeJsLintWrapperService')->setValidatorRawOutput(
            file_get_contents($this->getFixturesDataPath($this->getName() . '/NodeJslintResponse/1'))
        );     
    }      
    
    /**
     * @group standard
     */    
    public function testOff() {                
        $task = $this->getTask('http://example.com/', array(
            'jslint-option-passfail' => 0
        ));

        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals(self::NON_FILTERED_ERROR_COUNT, $task->getOutput()->getErrorCount());
    } 
    
    /**
     * @group standard
     */    
    public function testOn() {                
        $task = $this->getTask('http://example.com/', array(
            'jslint-option-passfail' => 1
        ));

        $this->assertEquals(0, $this->getTaskService()->perform($task));      
        $this->assertEquals(1, $task->getOutput()->getErrorCount());
    }
}
