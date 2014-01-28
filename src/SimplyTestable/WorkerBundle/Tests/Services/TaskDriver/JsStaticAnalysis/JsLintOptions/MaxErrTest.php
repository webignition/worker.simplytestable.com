<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\JsLintOptions;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\TaskDriverTest;

class MaxErrTest extends TaskDriverTest {
    
    const NON_FILTERED_ERROR_COUNT = 5;    
    
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
    public function testNoMaxErr() {                
        $task = $this->getDefaultTask();
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals(self::NON_FILTERED_ERROR_COUNT, $task->getOutput()->getErrorCount());
    } 
    
    /**
     * @group standard
     */    
    public function testMaxErr4() {
        $this->maxErrTest((int)str_replace('testMaxErr', '', __FUNCTION__));
    }   

    /**
     * @group standard
     */    
    public function testMaxErr3() {
        $this->maxErrTest((int)str_replace('testMaxErr', '', __FUNCTION__));
    } 
    
    /**
     * @group standard
     */    
    public function testMaxErr2() {
        $this->maxErrTest((int)str_replace('testMaxErr', '', __FUNCTION__));
    } 
    
    /**
     * @group standard
     */    
    public function testMaxErr1() {
        $this->maxErrTest((int)str_replace('testMaxErr', '', __FUNCTION__));
    } 
    
    /**
     * @group standard
     */    
    public function testMaxErr0() {        
        $task = $this->getTask('http://example.com/', array(
            'jslint-option-maxerr' => 0
        ));
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals(self::NON_FILTERED_ERROR_COUNT, $task->getOutput()->getErrorCount());
    }     
    
    
    private function maxErrTest($maxErr) {                
        $task = $this->getTask('http://example.com/', array(
            'jslint-option-maxerr' => $maxErr
        ));        
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals($maxErr, $task->getOutput()->getErrorCount());        
    }
}
