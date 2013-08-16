<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\UrlDiscovery;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class PerformCommandUrlDiscoveryTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabase();        
    }      
    
    
    /**
     * @group standard
     */    
    public function testPerformOnNonExistentUrl() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $taskObject = $this->createTask('http://example.com/', 'URL discovery');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);        
        $this->assertEquals(1, $task->getOutput()->getErrorCount());
        $this->assertEquals('{"messages":[{"message":"Not Found","messageId":"http-retrieval-404","type":"error"}]}', $task->getOutput()->getOutput());
    }
    
    
    /**
     * @group standard
     */    
    public function testPerformOnNonExistentHost() {        
        $taskObject = $this->createTask('http://invalid/', 'URL discovery');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);        
        $this->assertEquals(1, $task->getOutput()->getErrorCount());
        $this->assertEquals('{"messages":[{"message":"DNS lookup failure resolving resource domain name","messageId":"http-retrieval-curl-code-6","type":"error"}]}', $task->getOutput()->getOutput());        
    }
    
    
    /**
     * @group standard
     */    
    public function testPerformOnValidUrl() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

        $taskObject = $this->createTask('http://example.com/', 'URL discovery', '{"scope":"http:\/\/example.com\/"}');
        
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

        $taskObject = $this->createTask('http://example.com/', 'URL discovery', '{"scope":["http:\/\/example.com","http:\/\/www.example.com"]}');
        
        $task = $this->getTaskService()->getById($taskObject->id);

        $this->assertEquals(0, $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        )));
        
        $this->assertEquals(31, count(json_decode($task->getOutput()->getOutput())));
    } 
}