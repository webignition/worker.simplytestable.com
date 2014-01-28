<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\JsLintOptions;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\TaskDriverTest;

class MaxLenTest extends TaskDriverTest {
    
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
    public function testNoMaxLen() {
        $task = $this->getDefaultTask();
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals(0, $task->getOutput()->getErrorCount());
    }
    
    
    /**
     * @group standard
     */    
    public function testMaxLen32() {          
        $task = $this->getTask('http://example.com/', array(
            'jslint-option-maxlen' => 32
        ));
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals(0, $task->getOutput()->getErrorCount());
    }   
    
    
    /**
     * @group standard
     */    
    public function testMaxLen31() {         
        $task = $this->getTask('http://example.com/', array(
            'jslint-option-maxlen' => 31
        ));
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals(1, $task->getOutput()->getErrorCount());
    }
}
