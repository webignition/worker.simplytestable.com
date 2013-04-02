<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\CssValidation;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class PerformCommandCssValidationTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }    
    
    public function testPerformOnNonExistentUrl() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $taskObject = $this->createTask('http://blog.simplytestable.com/invalid', 'CSS validation');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);
        $this->assertEquals(1, $task->getOutput()->getErrorCount());
        $this->assertEquals('[{"message":"File not found","class":"css-validation-404","type":"error","context":"","ref":"http:\/\/blog.simplytestable.com\/invalid","line_number":0}]', $task->getOutput()->getOutput());
    }
    
    
    public function testPerformOnNonExistentHost() {        
        $taskObject = $this->createTask('http://invalid/', 'CSS validation');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);        
        $this->assertEquals(1, $task->getOutput()->getErrorCount());
        
        $this->assertEquals('[{"message":"Unknown-host","class":"css-validation-6","type":"error","context":"","ref":"http:\/\/invalid\/","line_number":0}]', $task->getOutput()->getOutput());        
    }    


}
