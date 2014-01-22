<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\JsLintOptions;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis\TaskDriverTest;

class IndentTest extends TaskDriverTest {
    
    const NON_FILTERED_ERROR_COUNT = 1;
    
    /**
     * @group standard
     */    
    public function testNoIndent() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath()));
        
        $task = $this->getDefaultTask();
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals(self::NON_FILTERED_ERROR_COUNT, $task->getOutput()->getErrorCount());
    } 
    
    /**
     * @group standard
     */    
    public function testIndent2() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath()));
        
        $task = $this->getTask('http://example.com/', array(
            'jslint-option-indent' => '2'
        ));
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals(0, $task->getOutput()->getErrorCount());
    }
    
    protected function getFixturesDataPath($testName = null) {
        $fixturesDataPathParts = explode('/', parent::getFixturesDataPath(__FUNCTION__));        
        return implode('/', array_slice($fixturesDataPathParts, 0, count($fixturesDataPathParts) - 1)) . '/HttpResponses'; 
    }


}
