<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\UrlDiscovery;

use SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\PerformCommandTaskTypeTest;

class PerformCommandUrlDiscoveryTest extends PerformCommandTaskTypeTest {
    
    const TASK_TYPE_NAME = 'URL discovery';
    
    protected function getTaskTypeName() {
        return self::TASK_TYPE_NAME;
    }
    
    
    /**
     * @group standard
     */    
    public function testPerformOnValidUrl() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

        $taskObject = $this->createTask('http://example.com/', $this->getTaskTypeName(), '{"scope":"http:\/\/example.com\/"}');
        
        $task = $this->getTaskService()->getById($taskObject->id);

        $this->assertEquals(0, $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        )));
        
        $this->assertEquals(array(
            "http://example.com/",
            "http://example.com/articles/",
            "http://example.com/articles/symfony-container-aware-migrations/",
            "http://example.com/articles/i-make-the-internet/",
            "http://example.com/articles/getting-to-building-simpytestable-dot-com/"
        ), json_decode($task->getOutput()->getOutput()));
    } 
    
    
    
    /**
     * @group standard
     */    
    public function testTreatWwwAndNonWwwAsEquivalent() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

        $taskObject = $this->createTask('http://example.com/', $this->getTaskTypeName(), '{"scope":["http:\/\/example.com","http:\/\/www.example.com"]}');
        
        $task = $this->getTaskService()->getById($taskObject->id);

        $this->assertEquals(0, $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        )));
        
        $this->assertEquals(31, count(json_decode($task->getOutput()->getOutput())));
    }
    

    /**
     * @group standard
     */     
    public function testWithHttpAuthProtectedPage() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

        $taskObject = $this->createTask('http://http-auth-04.simplytestable.com/', 'URL discovery', json_encode(array(
            'scope' => 'http://http-auth-04.simplytestable.com/',
            'http-auth-username' => 'example',
            'http-auth-password' => 'password'
        ))); 
        
        $task = $this->getTaskService()->getById($taskObject->id);

        $this->assertEquals(0, $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        )));
        
        $this->assertEquals(array(
            'http://http-auth-04.simplytestable.com/two.html',
            'http://http-auth-04.simplytestable.com/three.html'
        ), json_decode($task->getOutput()->getOutput()));     
    }
    
    
    public function testDiscoveredRelativeUrlsAreReportedInAbsoluteFormInOutput() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

        $taskObject = $this->createTask('http://example.com/', 'URL discovery', json_encode(array(
            'scope' => 'http://example.com/'
        ))); 
        
        $task = $this->getTaskService()->getById($taskObject->id);

        $this->assertEquals(0, $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        )));
        
        $this->assertEquals(array(
            'http://example.com/',
            'http://example.com/contact.php',
            'http://example.com/register/',
        ), json_decode($task->getOutput()->getOutput()));             
    }
    
    public function testDiscoveredUrlsAreOfCorrectAbsoluteForm() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses'))); 
        
        $taskObject = $this->createTask('http://example.com/foo', 'URL discovery', json_encode(array(
            'scope' => 'http://example.com/'            
        )));         
        
        $task = $this->getTaskService()->getById($taskObject->id);

        $this->assertEquals(0, $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        )));
        
        $this->assertEquals(array(
            'http://example.com/foo/foo.html',
            'http://example.com/bar/',
            'http://example.com/foo/foo/bar/',
        ), json_decode($task->getOutput()->getOutput()));             
    }    
}