<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\HtmlValidation;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class PerformCommandHtmlValidationTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabase();        
    }    
    
    
    /**
     * @group standard
     */    
    public function testPerformOnNonExistentUrl() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $taskObject = $this->createTask('http://example.com/', 'HTML validation');
        
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
        
        $taskObject = $this->createTask('http://invalid/', 'HTML validation');
        
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
    public function testGetContentTypeWhenInMarkupContentTypeIsInvalid() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $taskObject = $this->createTask('http://example.com/', 'HTML validation');       
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);
    }
    
    
    public function testFailGracefullyWhenContentIsServedAsTextHtmlButIsNot() {
        $this->clearMemcacheHttpCache();  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $taskObject = $this->createTask('http://example.com/', 'HTML validation');       
     
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));
        
        $this->assertEquals(0, $response);        
        
        $outputObject = json_decode($task->getOutput()->getOutput());
        
        $this->assertEquals('document-is-not-markup', $outputObject->messages[0]->messageId);
    }


}
