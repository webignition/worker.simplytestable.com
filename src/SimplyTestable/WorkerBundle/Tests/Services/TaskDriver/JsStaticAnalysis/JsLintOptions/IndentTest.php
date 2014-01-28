<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\JsLintOptions;

class IndentTest extends JsLintOptionsTest {
    
    const NON_FILTERED_ERROR_COUNT = 1;   
    
    /**
     * @group standard
     */    
    public function testNoIndent() {        
        $task = $this->getDefaultTask();
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals(self::NON_FILTERED_ERROR_COUNT, $task->getOutput()->getErrorCount());
    } 
    
    /**
     * @group standard
     */    
    public function testIndent2() {        
        $task = $this->getTask('http://example.com/', array(
            'jslint-option-indent' => '2'
        ));
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals(0, $task->getOutput()->getErrorCount());
    }
}
