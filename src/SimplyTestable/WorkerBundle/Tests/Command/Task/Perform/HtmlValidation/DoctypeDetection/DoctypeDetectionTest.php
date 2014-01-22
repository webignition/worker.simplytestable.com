<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\HtmlValidation\DoctypeDetection;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class DoctypeDetectionTest extends ConsoleCommandBaseTestCase {
    

    /**
     * @group standard
     */        
    public function testFailedDueToNoDoctypeHasFailedState() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

        $taskObject = $this->createTask('http://example.com/', 'HTML validation');  
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));        
  
        $this->assertEquals($this->getTaskService()->getFailedNoRetryAvailableState(), $task->getState());
    }    
    
    
    /**
     * @group standard
     */        
    public function testFailedDueToNoDoctypeHasCorrectFailureReason() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

        $taskObject = $this->createTask('http://example.com/', 'HTML validation');  
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));  
        
        $decodedTaskOutput = json_decode($task->getOutput()->getOutput());
        $this->assertEquals('document-type-missing', $decodedTaskOutput->messages[0]->messageId);
    }    
    
    
    /**
     * @group standard
     */        
    public function testFailedDueToInvalidDoctypeHasFailedState() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

        $taskObject = $this->createTask('http://example.com/', 'HTML validation');  
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));        
  
        $this->assertEquals($this->getTaskService()->getFailedNoRetryAvailableState(), $task->getState());
    }
    
    
    /**
     * @group standard
     */        
    public function testFailedDueToInvalidDoctypeHasCorrectFailureReason() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

        $taskObject = $this->createTask('http://example.com/', 'HTML validation');  
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));  
        
        $decodedTaskOutput = json_decode($task->getOutput()->getOutput());
        $this->assertEquals('document-type-invalid', $decodedTaskOutput->messages[0]->messageId);
    }
    
    
    
    /**
     * @group standard
     */        
    public function testSingleLineCommentPrecedingDoctypeDoesNotHinderDoctypeDetection() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $this->container->get('simplytestable.services.htmlValidatorWrapperService')->loadFixturesFromPath(
            $this->getFixturesDataPath(__FUNCTION__ . '/HtmlValidatorResponses')
        );          

        $taskObject = $this->createTask('http://example.com/', 'HTML validation');  
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals($this->getTaskService()->getCompletedState(), $task->getState());
    }
    
    
    /**
     * @group standard
     */        
    public function testMultilineCommentPrecedingDoctypeDoesNotHinderDoctypeDetection() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $this->container->get('simplytestable.services.htmlValidatorWrapperService')->loadFixturesFromPath(
            $this->getFixturesDataPath(__FUNCTION__ . '/HtmlValidatorResponses')
        );          

        $taskObject = $this->createTask('http://example.com/', 'HTML validation');  
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals($this->getTaskService()->getCompletedState(), $task->getState());
    }    
    
}
