<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\JsLintOptions;

class PassFailTest extends JsLintOptionsTest {
    
    const NON_FILTERED_ERROR_COUNT = 2;       
    
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
