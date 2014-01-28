<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\JsLintOptions;

class PredefTest extends JsLintOptionsTest {
    
    const NON_FILTERED_ERROR_COUNT = 3;      
    
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
