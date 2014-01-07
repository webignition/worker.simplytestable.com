<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform\CssValidation;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class MultipleChildStylesheetCurlErrorTest extends ConsoleCommandBaseTestCase {    
    
    public function setUp() {
        parent::setUp();
        
        $this->clearMemcacheHttpCache();
        
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            file_get_contents($this->getFixturesDataPath() . '/HttpResponses/1'),
            file_get_contents($this->getFixturesDataPath() . '/HttpResponses/2'),
            'CURL/' . str_replace('test', '', $this->getName().' Not-relevant worded error')
        )));
        
        $this->getHttpClientService()->disablePlugin('Guzzle\Plugin\Backoff\BackoffPlugin');
        
        $this->container->get('simplytestable.services.cssValidatorWrapperService')->setCssValidatorRawOutput(
            file_get_contents($this->getFixturesDataPath() . '/CssValidatorResponse/1')
        );        
    }
    
    
    /**
     * @group standard
     */       
    public function test6() {
        $this->assertHttpRetrievalFailureCodeMatchesReceivedResponse(str_replace('test', '', $this->getName()));
    }    
    
    
    /**
     * @group standard
     */       
    public function test28() {
        $this->assertHttpRetrievalFailureCodeMatchesReceivedResponse(str_replace('test', '', $this->getName()));
    }    
    
    public function assertHttpRetrievalFailureCodeMatchesReceivedResponse($statusCode) {
        $taskObject = $this->createTask('http://example.com/', 'CSS Validation');         

        $task = $this->getTaskService()->getById($taskObject->id);

        $response = $this->runConsole('simplytestable:task:perform', array(
            $task->getId() => true
        ));

        $this->assertEquals(0, $response);  
        $this->assertEquals(1, $task->getOutput()->getErrorCount());
        $this->assertEquals('[{"message":"http-retrieval-curl-code-'.$statusCode.'","context":"","line_number":0,"type":"error","ref":"http:\/\/example.com\/style2.css"}]', $task->getOutput()->getOutput());
    }   

}
