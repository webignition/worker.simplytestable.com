<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\JsLintOptions;

class MaxLenTest extends JsLintOptionsTest {   
    
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
