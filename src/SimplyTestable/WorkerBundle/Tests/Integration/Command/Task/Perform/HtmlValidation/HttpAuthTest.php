<?php

namespace SimplyTestable\WorkerBundle\Tests\Integration\Command\Task\Perform\HtmlValidation;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class HttpAuthTest extends ConsoleCommandBaseTestCase {
    
    public static function setUpBeforeClass() {
        self::setupDatabase();        
    }
  
    
    /**
     * @group integration
     * @group integration-html-validation
     * @group integration-travis
     * @group integration-html-validation-travis
     */     
    public function testSiteWithHttpAuthProtectionWithValidCredentials() {        
        $taskObject = $this->createTask('http://unreliable.simplytestable.com/http-auth/index.html', 'HTML validation', json_encode(array(
            'http-auth' => array(
                'username' => 'example',
                'password' => 'password'
            )
        )));
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);        
        $this->assertEquals('{"messages":[]}', $task->getOutput()->getOutput());
    } 
    
    
    /**
     * @group integration
     * @group integration-html-validation
     * @group integration-travis
     * @group integration-html-validation-travis
     */     
    public function testSiteWithHttpAuthProtectionWithInvalidCredentials() {
        $this->getHttpClientService()->getMemcacheCache()->deleteAll();
        
        $taskObject = $this->createTask('http://unreliable.simplytestable.com/http-auth/index.html', 'HTML validation', json_encode(array(
            'http-auth' => array(
                'username' => 'wrong-username',
                'password' => 'wrong-password'
            )
        )));
        
        $task = $this->getTaskService()->getById($taskObject->id);
        
        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);
        $this->assertEquals('{"messages":[{"message":"Unauthorized","messageId":"http-retrieval-401","type":"error"}]}', $task->getOutput()->getOutput());        
        $this->assertTrue($task->getParametersObject()->{'http-auth'}->{'has-tried'});
    }     
}

