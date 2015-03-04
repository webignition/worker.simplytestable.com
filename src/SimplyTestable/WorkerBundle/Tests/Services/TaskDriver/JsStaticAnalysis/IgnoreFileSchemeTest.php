<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis;

class IgnoreFileSchemeTest extends TaskDriverTest {
    
    /**
     * @group standard
     */      
    public function testUrlsWithFileSchemeAreIgnored() {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath($this->getName()) . '/HttpResponses'));    
        
        $task = $this->getTask('http://example.com/');
        $this->getTaskService()->perform($task);

        $this->assertEquals([], json_decode($task->getOutput()->getOutput(), true));
    }
}
