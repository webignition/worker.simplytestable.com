<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\HtmlValidation;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\HtmlValidation\TaskDriverTest;

class DoctypeDetectionTest extends TaskDriverTest {
    
    private $task;
    
    public function setUp() {
        parent::setUp();
        $this->task = $this->getDefaultTask();
    }
    
    

    /**
     * @group standard
     */        
    public function testFailedDueToNoDoctypeHasFailedState() {  
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $this->assertEquals(0, $this->getTaskService()->perform($this->task));  
        $this->assertEquals($this->getTaskService()->getFailedNoRetryAvailableState(), $this->task->getState());
    }    
    
    
    /**
     * @group standard
     */        
    public function testFailedDueToNoDoctypeHasCorrectFailureReason() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $this->assertEquals(0, $this->getTaskService()->perform($this->task));
        
        $decodedTaskOutput = json_decode($this->task->getOutput()->getOutput());
        $this->assertEquals('document-type-missing', $decodedTaskOutput->messages[0]->messageId);
    }    
    
    
    /**
     * @group standard
     */        
    public function testFailedDueToInvalidDoctypeHasFailedState() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $this->assertEquals(0, $this->getTaskService()->perform($this->task));  
        $this->assertEquals($this->getTaskService()->getFailedNoRetryAvailableState(), $this->task->getState());
    }
    
    
    /**
     * @group standard
     */        
    public function testFailedDueToInvalidDoctypeHasCorrectFailureReason() { 
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));

        $this->assertEquals(0, $this->getTaskService()->perform($this->task));  
        
        $decodedTaskOutput = json_decode($this->task->getOutput()->getOutput());
        $this->assertEquals('document-type-invalid', $decodedTaskOutput->messages[0]->messageId);
    }
    
    
    
    /**
     * @group standard
     */        
    public function testSingleLineCommentPrecedingDoctypeDoesNotHinderDoctypeDetection() { 
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $this->container->get('simplytestable.services.htmlValidatorWrapperService')->loadFixturesFromPath(
            $this->getFixturesDataPath(__FUNCTION__ . '/HtmlValidatorResponses')
        );          

        $this->assertEquals(0, $this->getTaskService()->perform($this->task));        
        $this->assertEquals($this->getTaskService()->getCompletedState(), $this->task->getState());
    }
    
    
    /**
     * @group standard
     */        
    public function testMultilineCommentPrecedingDoctypeDoesNotHinderDoctypeDetection() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $this->container->get('simplytestable.services.htmlValidatorWrapperService')->loadFixturesFromPath(
            $this->getFixturesDataPath(__FUNCTION__ . '/HtmlValidatorResponses')
        );          

        $this->assertEquals(0, $this->getTaskService()->perform($this->task));        
        $this->assertEquals($this->getTaskService()->getCompletedState(), $this->task->getState());
    }  
    
    
    public function testWhitespaceInDoctypeFpiDoesNotHinderDoctypeDetectionOrExtraction() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        
        $this->container->get('simplytestable.services.htmlValidatorWrapperService')->loadFixturesFromPath(
            $this->getFixturesDataPath(__FUNCTION__ . '/HtmlValidatorResponses')
        );          
        
        $this->task = $this->getTask('http://site.terradoboi.com/');

        $this->assertEquals(0, $this->getTaskService()->perform($this->task));        
        $this->assertEquals($this->getTaskService()->getCompletedState(), $this->task->getState());        
    }
    
}
