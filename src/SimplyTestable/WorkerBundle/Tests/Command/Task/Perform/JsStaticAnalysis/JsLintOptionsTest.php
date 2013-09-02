<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\JsStaticAnalysis;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class JsLintOptionsTest extends ConsoleCommandBaseTestCase {
    
    const NON_FILTERED_ERROR_COUNT = 6;
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }    
    
/**
 * {"jslint-option-passfail":"0","jslint-option-bitwise":"1","jslint-option-continue":"1","jslint-option-debug":"1","jslint-option-evil":"1","jslint-option-eqeq":"1","jslint-option-es5":"0","jslint-option-forin":"1","jslint-option-newcap":"1","jslint-option-nomen":"1","jslint-option-plusplus":"1","jslint-option-regexp":"1","jslint-option-undef":"1","jslint-option-unparam":"1","jslint-option-sloppy":"1","jslint-option-stupid":"1","jslint-option-sub":"1","jslint-option-vars":"1","jslint-option-white":"1","jslint-option-anon":"1","jslint-option-browser":"1","jslint-option-devel":"0","jslint-option-windows":"0","jslint-option-maxerr":"50","jslint-option-indent":"4","jslint-option-maxlen":"256","jslint-option-predef":[""]}
 * {"jslint-option-continue":"1","jslint-option-debug":"1","jslint-option-evil":"1","jslint-option-eqeq":"1","jslint-option-es5":"0","jslint-option-forin":"1","jslint-option-newcap":"1","jslint-option-nomen":"1","jslint-option-plusplus":"1","jslint-option-regexp":"1","jslint-option-undef":"1","jslint-option-unparam":"1","jslint-option-sloppy":"1","jslint-option-stupid":"1","jslint-option-sub":"1","jslint-option-vars":"1","jslint-option-white":"1","jslint-option-anon":"1","jslint-option-browser":"1","jslint-option-devel":"0","jslint-option-windows":"0","jslint-option-maxerr":"50","jslint-option-indent":"4","jslint-option-maxlen":"256","jslint-option-predef":[""]}
 * {"jslint-option-passfail":"0"}
 * {"jslint-option-bitwise":"0"}
 * {"jslint-option-continue":"0"}
 */    
    
    
    /**
     * @group standard
     */    
    public function testPassFailOff() {        
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath()));
        
        $taskObject = $this->createTask('http://example.com/', 'JS static analysis', '{"jslint-option-passfail":"0"}');
        
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
    public function testPassFailOn() {        
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath()));
        
        $taskObject = $this->createTask('http://example.com/', 'JS static analysis', '{"jslint-option-passfail":"1"}');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);        
        $this->assertEquals(1, $task->getOutput()->getErrorCount());
    }
    
    /**
     * @group standard
     */    
    public function testBitwiseOff() {        
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath()));
        
        $taskObject = $this->createTask('http://example.com/', 'JS static analysis', '{"jslint-option-bitwise":"0"}');
        
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
    public function testBitwiseOn() {        
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath()));
        
        $taskObject = $this->createTask('http://example.com/', 'JS static analysis', '{"jslint-option-bitwise":"1"}');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);        
        $this->assertEquals(self::NON_FILTERED_ERROR_COUNT - 1, $task->getOutput()->getErrorCount());
    } 
    
    /**
     * @group standard
     */    
    public function testContinueOff() {        
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath()));
        
        $taskObject = $this->createTask('http://example.com/', 'JS static analysis', '{"jslint-option-continue":"0"}');
        
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
    public function testContinueOn() {        
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath()));
        
        $taskObject = $this->createTask('http://example.com/', 'JS static analysis', '{"jslint-option-continue":"1"}');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);        
        $this->assertEquals(self::NON_FILTERED_ERROR_COUNT - 1, $task->getOutput()->getErrorCount());
    }    
    
    protected function getFixturesDataPath() {
        $fixturesDataPathParts = explode('/', parent::getFixturesDataPath(__FUNCTION__));        
        return implode('/', array_slice($fixturesDataPathParts, 0, count($fixturesDataPathParts) - 1)) . '/HttpResponses'; 
    }


}
