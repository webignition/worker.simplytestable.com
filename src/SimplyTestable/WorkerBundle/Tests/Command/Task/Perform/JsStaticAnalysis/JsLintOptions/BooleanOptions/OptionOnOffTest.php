<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\JsStaticAnalysis\JsLintOptions\BooleanOptions;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

abstract class OptionOnOffTest extends ConsoleCommandBaseTestCase {

    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }
    
    protected function offTest($className) {          
        $this->withValueTest($className, '0');     
    }    
    
    protected function onTest($className) {        
        $this->withValueTest($className, '1');    
    }
    
    private function withValueTest($className, $value) {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(null) . '/HttpResponses'));
        
        $taskObject = $this->createTask('http://example.com/', 'JS static analysis', '{"jslint-option-'.$this->getOptionNameFromClassName($className).'":"'.$value.'"}');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $expectedErrorCount = ($value === '1') ? 0 : 1;
        
        

        $this->assertEquals(0, $response);
        
//        var_dump($task->getOutput()->getOutput());
//        exit();
        
        $this->assertEquals($expectedErrorCount, $task->getOutput()->getErrorCount());           
        
        
    }
    
    private function getOptionNameFromClassName($className) {
        $classNameParts = explode('\\', $className);        
        return strtolower(str_replace('Test', '', $classNameParts[count($classNameParts) - 1]));
    }


}
