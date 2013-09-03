<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\JsStaticAnalysis\JsLintOptions;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class PredefTest extends ConsoleCommandBaseTestCase {
    
    const NON_FILTERED_ERROR_COUNT = 3;
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }    
    
    /**
     * @group standard
     */    
    public function testNoPredef() {        
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath()));
        
        $taskObject = $this->createTask('http://example.com/', 'JS static analysis');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);
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
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath()));
        
        $taskObject = $this->createTask('http://example.com/', 'JS static analysis', '{"jslint-option-predef":"'.implode(' ', $values).'"}');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $expecteErrorCount = self::NON_FILTERED_ERROR_COUNT - count($values);
        if ($expecteErrorCount < 0) {
            $expecteErrorCount = 0;
        }

        $this->assertEquals(0, $response);
        $this->assertEquals($expecteErrorCount, $task->getOutput()->getErrorCount());        
    }   
    
    protected function getFixturesDataPath() {
        $fixturesDataPathParts = explode('/', parent::getFixturesDataPath(__FUNCTION__));        
        return implode('/', array_slice($fixturesDataPathParts, 0, count($fixturesDataPathParts) - 1)) . '/HttpResponses'; 
    }


}
