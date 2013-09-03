<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\JsStaticAnalysis\JsLintOptions;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class MaxErrTest extends ConsoleCommandBaseTestCase {
    
    const NON_FILTERED_ERROR_COUNT = 5;
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }    
    
    /**
     * @group standard
     */    
    public function testNoMaxErr() {        
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath()));
        
        $taskObject = $this->createTask('http://example.com/', 'JS static analysis');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);
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
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath()));
        
        $taskObject = $this->createTask('http://example.com/', 'JS static analysis', '{"jslint-option-maxerr":"0"}');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);
        $this->assertEquals(self::NON_FILTERED_ERROR_COUNT, $task->getOutput()->getErrorCount());
    }     
    
    
    private function maxErrTest($maxErr) {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath()));
        
        $taskObject = $this->createTask('http://example.com/', 'JS static analysis', '{"jslint-option-maxerr":"'.$maxErr.'"}');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);
        $this->assertEquals($maxErr, $task->getOutput()->getErrorCount());        
    }   
    
    protected function getFixturesDataPath() {
        $fixturesDataPathParts = explode('/', parent::getFixturesDataPath(__FUNCTION__));        
        return implode('/', array_slice($fixturesDataPathParts, 0, count($fixturesDataPathParts) - 1)) . '/HttpResponses'; 
    }


}
