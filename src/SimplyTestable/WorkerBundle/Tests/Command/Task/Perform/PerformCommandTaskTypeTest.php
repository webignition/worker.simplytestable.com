<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

abstract class PerformCommandTaskTypeTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    } 
    
    abstract protected function getTaskTypeName();    
    
    /**
     * @group standard
     */    
    public function testPerformOnNonExistentUrl() {        
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.1 404 Not Found'
        )));
        
        $taskObject = $this->createTask('http://example.com/invalid', $this->getTaskTypeName());
        
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
        $this->getWebResourceService()->setRequestSkeletonToCurlErrorMap(array(
            'http://invalid/' => array(
                'GET' => array(
                    'errorMessage' => "Couldn't resolve host. The given remote host was not resolved.",
                    'errorNumber' => 6                    
                )
            )
        ));         
        
        $taskObject = $this->createTask('http://invalid/', $this->getTaskTypeName());
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);        
        $this->assertEquals(1, $task->getOutput()->getErrorCount());
        
        $this->assertEquals('{"messages":[{"message":"DNS lookup failure resolving resource domain name","messageId":"http-retrieval-curl-code-6","type":"error"}]}', $task->getOutput()->getOutput());        
    }    


}
