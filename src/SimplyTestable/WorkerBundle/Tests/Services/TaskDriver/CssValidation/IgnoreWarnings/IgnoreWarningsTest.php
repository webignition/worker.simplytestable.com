<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\IgnoreWarnings;

use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation\TaskDriverTest;

abstract class IgnoreWarningsTest extends TaskDriverTest {
    
    private $task;
    private $performResult;
    
    public function setUp() {
        parent::setUp();
        
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(null, 1) . '/HttpResponses'));
        $this->container->get('simplytestable.services.cssValidatorWrapperService')->setCssValidatorRawOutput(
            file_get_contents($this->getFixturesDataPath(null, 1) . '/CssValidatorResponse/1')
        );
        
        $this->task = $this->getTask('http://example.com/', $this->getTaskParameters());
        $this->performResult = $this->getTaskService()->perform($this->task);
    }
    
    abstract protected function getExpectedWarningCount();
    abstract protected function getTaskParameters();
    
    
    public function testTaskIsPerformed() {
        $this->assertEquals(0, $this->performResult);
    }
    
    
    public function testErrorCount() {
        $this->assertEquals(0, $this->task->getOutput()->getErrorCount());
    }
    
    public function testWarningCount() {
        $this->assertEquals($this->getExpectedWarningCount(), $this->task->getOutput()->getWarningCount());
    }
    
    public function testCurlOptionsAreSetOnAllRequests() {
        $this->assertSystemCurlOptionsAreSetOnAllRequests();
    }      

}
