<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\JsStaticAnalysis;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class PerformJsStaticAnalysisTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabaseIfNotExists();        
    }    
    
    
    /**
     * @group standard
     */    
    public function testPerformOnNonExistentUrl() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $taskObject = $this->createTask('http://blog.simplytestable.com/invalid', 'JS static analysis');
        
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
        
        $taskObject = $this->createTask('http://invalid/', 'JS static analysis');
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);        
        $this->assertEquals(1, $task->getOutput()->getErrorCount());
        
        $this->assertEquals('{"messages":[{"message":"DNS lookup failure resolving resource domain name","messageId":"http-retrieval-curl-code-6","type":"error"}]}', $task->getOutput()->getOutput());        
    }

}
