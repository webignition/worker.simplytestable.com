<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\JsLintOptions;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\TaskDriverTest;

class MaxLenTest extends TaskDriverTest {
    
    /**
     * @group standard
     */    
    public function testNoMaxLen() {        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath()));
        
        $task = $this->getDefaultTask();
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals(0, $task->getOutput()->getErrorCount());
    }
    
    
    /**
     * @group standard
     */    
    public function testMaxLen32() {          
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath()));
        
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
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath()));
        
        $task = $this->getTask('http://example.com/', array(
            'jslint-option-maxlen' => 31
        ));
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals(1, $task->getOutput()->getErrorCount());
    }    
    
    protected function getFixturesDataPath($testName = null) {
        $fixturesDataPathParts = explode('/', parent::getFixturesDataPath(__FUNCTION__));        
        return implode('/', array_slice($fixturesDataPathParts, 0, count($fixturesDataPathParts) - 1)) . '/HttpResponses'; 
    }


}