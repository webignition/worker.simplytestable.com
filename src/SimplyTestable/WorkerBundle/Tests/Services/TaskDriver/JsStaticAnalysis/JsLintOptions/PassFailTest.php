<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\JsLintOptions;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\TaskDriverTest;

class PassFailTest extends TaskDriverTest {
    
    const NON_FILTERED_ERROR_COUNT = 2;  
    
    /**
     * @group standard
     */    
    public function testOff() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath()));
                
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
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath()));
        
        $task = $this->getTask('http://example.com/', array(
            'jslint-option-passfail' => 1
        ));

        $this->assertEquals(0, $this->getTaskService()->perform($task));      
        $this->assertEquals(1, $task->getOutput()->getErrorCount());
    }     
    
    protected function getFixturesDataPath($testName = null) {
        $fixturesDataPathParts = explode('/', parent::getFixturesDataPath(__FUNCTION__));        
        return implode('/', array_slice($fixturesDataPathParts, 0, count($fixturesDataPathParts) - 1)) . '/HttpResponses'; 
    }


}
