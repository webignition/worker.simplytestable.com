<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\LinkIntegrity;

use SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\PerformCommandTaskTypeTest;

class PerformCommandLinkIntegrityTest extends PerformCommandTaskTypeTest {
    
    const TASK_TYPE_NAME = 'Link integrity';
    
    protected function getTaskTypeName() {
        return self::TASK_TYPE_NAME;
    }
    
    
    /**
     * @group standard
     */    
    public function testPerformWithNoBrokenLinks() {
        $this->clearMemcacheHttpCache();
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

        $taskObject = $this->createTask('http://example.com/', $this->getTaskTypeName());
        
        $task = $this->getTaskService()->getById($taskObject->id);

        $this->assertEquals(0, $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        )));       
      
        $this->assertEquals(array(), json_decode($task->getOutput()->getOutput()));
    }
    
    /**
     * @group standard
     */    
    public function testPerformWithOneHttp404() {
        $this->clearMemcacheHttpCache();
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

        $taskObject = $this->createTask('http://example.com/', $this->getTaskTypeName());
        
        $task = $this->getTaskService()->getById($taskObject->id);

        $this->assertEquals(0, $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        )));       
        
        $erroredLink = new \stdClass();
        $erroredLink->context = '<link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/css/bootstrap.no-icons.min.css" rel="stylesheet">';
        $erroredLink->state = 404;
        $erroredLink->type = 'http';
        $erroredLink->url = 'http://netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/css/bootstrap.no-icons.min.css';
      
        $this->assertEquals(array(
            $erroredLink
        ), json_decode($task->getOutput()->getOutput()));
    }   
}