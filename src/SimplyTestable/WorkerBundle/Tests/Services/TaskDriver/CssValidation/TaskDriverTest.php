<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\BaseTest;

abstract class TaskDriverTest extends BaseTest {

    protected function getTaskTypeName() {
        return 'CSS Validation';
    }
//    
//    protected $task;
//    private $performResult;    
//    
//    public function setUp() {
//        parent::setUp();
//        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getFixtureTestName(), $this->getFixtureUpLevelsCount()). '/HttpResponses'));
//
//        $this->container->get('simplytestable.services.cssValidatorWrapperService')->setCssValidatorRawOutput(
//            file_get_contents($this->getFixturesDataPath(null, 1) . '/CssValidatorResponse/1')
//        );      
//        
//        $this->task = $this->getTask('http://example.com/', $this->getTaskParameters());
//        $this->performResult = $this->getTaskService()->perform($this->task);        
//    }
//    
//    abstract protected function getFixtureTestName();
//    abstract protected function getFixtureUpLevelsCount();
//    
//    abstract protected function getExpectedErrorCount();
//    abstract protected function getExpectedWarningCount();
//    abstract protected function getTaskParameters();    
//    
//    public function testTaskIsPerformed() {
//        $this->assertEquals(0, $this->performResult);
//    }    
//    
//    public function testErrorCount() {
//        $this->assertEquals($this->getExpectedErrorCount(), $this->task->getOutput()->getErrorCount());
//    }
//    
//    public function testWarningCount() {
//        $this->assertEquals($this->getExpectedWarningCount(), $this->task->getOutput()->getWarningCount());
//    }    
//    
//    
//    public function testCurlOptionsAreSetOnAllRequests() {
//        $this->assertSystemCurlOptionsAreSetOnAllRequests();
//    }    

}
