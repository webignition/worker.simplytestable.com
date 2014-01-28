<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\JsLintOptions;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\TaskDriverTest;

class PredefTest extends TaskDriverTest {
    
    const NON_FILTERED_ERROR_COUNT = 3;   
    
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
    public function testNoPredef() {
        $task = $this->getDefaultTask();
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals(3, $task->getOutput()->getErrorCount());
    } 
    
    /**
     * @group standard
     */    
    public function testPredefOne() {
        $this->prefdefTest(array('one'));
    }
    
    /**
     * @group standard
     */    
    public function testPredefTwo() {
        $this->prefdefTest(array('two'));
    }     
    
    /**
     * @group standard
     */    
    public function testPredefThree() {
        $this->prefdefTest(array('three'));
    } 
    
    /**
     * @group standard
     */    
    public function testPredefOneTwo() {
        $this->prefdefTest(array('one', 'two'));
    }      
    
    /**
     * @group standard
     */    
    public function testPredefOneTwoThree() {
        $this->prefdefTest(array('one', 'two', 'three'));
    }        
    
    /**
     * @group standard
     */    
    public function testPredefOneTwoThreeFoo() {
        $this->prefdefTest(array('one', 'two', 'three', 'foo'));
    }    
    
    
    private function prefdefTest($values) {        
        $task = $this->getTask('http://example.com/', array(
            'jslint-option-predef' => implode(' ', $values)
        ));
        
        $expecteErrorCount = self::NON_FILTERED_ERROR_COUNT - count($values);
        if ($expecteErrorCount < 0) {
            $expecteErrorCount = 0;
        }

        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals($expecteErrorCount, $task->getOutput()->getErrorCount());        
    } 


}
