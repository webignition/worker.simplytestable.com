<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\DomainsToIgnore;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\TaskDriverTest;

class DomainsToIgnoreTest extends TaskDriverTest {
    
    public function setUp() {
        parent::setUp();
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(). '/HttpResponses'));

        $this->container->get('simplytestable.services.cssValidatorWrapperService')->setCssValidatorRawOutput(
            file_get_contents($this->getFixturesDataPath() . '/CssValidatorResponse/1')
        );        
    }
    
    /**
     * @group standard
     */        
    public function testDomainsToIgnoreNotSet() {        
        $task = $this->getDefaultTask();        
       
        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals(3, $task->getOutput()->getErrorCount());
        $this->assertEquals(0, $task->getOutput()->getWarningCount());     
    }    
    
    /**
     * @group standard
     */        
    public function testDomainsToIgnoreOneDomainOfThree() {            
        $task = $this->getTask('http://example.com/', array(
            'domains-to-ignore' => array(
                'one.cdn.example.com'
            )             
        ));
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals(2, $task->getOutput()->getErrorCount());
        $this->assertEquals(0, $task->getOutput()->getWarningCount());     
    }  
    
  
    /**
     * @group standard
     */     
    public function testDomainsToIgnoreTwoDomainsOfThree() {        
        $task = $this->getTask('http://example.com/', array(
            'domains-to-ignore' => array(
                'one.cdn.example.com',
                'two.cdn.example.com'
            )               
        ));
        
        $this->assertEquals(0, $this->getTaskService()->perform($task));
        $this->assertEquals(1, $task->getOutput()->getErrorCount());
        $this->assertEquals(0, $task->getOutput()->getWarningCount());     
    }
    
    
    public function testCurlOptionsAreSetOnAllRequests() {
        $this->assertSystemCurlOptionsAreSetOnAllRequests();
    }      

}
