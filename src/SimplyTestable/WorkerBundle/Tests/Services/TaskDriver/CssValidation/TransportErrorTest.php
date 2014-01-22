<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\TaskDriverTest;

abstract class TransportErrorTest extends TaskDriverTest {    
    
    public function setUp() {
        parent::setUp();
        
        $this->setHttpFixtures($this->getFixtureSet());        
        $this->getHttpClientService()->disablePlugin('Guzzle\Plugin\Backoff\BackoffPlugin');
        
        $this->container->get('simplytestable.services.cssValidatorWrapperService')->setCssValidatorRawOutput(
            file_get_contents($this->getFixturesDataPath() . '/CssValidatorResponse/1')
        );        
    } 
    
    abstract protected function getFixtureSet();
    abstract protected function getOutputTemplate();
    
    public function assertRetrievalFailureCodeMatchesReceivedResponse($statusCode) {
        $task = $this->getDefaultTask();        
       
        $this->assertEquals(0, $this->getTaskService()->perform($task));          
        $this->assertEquals(1, $task->getOutput()->getErrorCount());
        $this->assertEquals(str_replace('{{failure-code}}', $statusCode, $this->getOutputTemplate()), $task->getOutput()->getOutput());             
    }     

}
