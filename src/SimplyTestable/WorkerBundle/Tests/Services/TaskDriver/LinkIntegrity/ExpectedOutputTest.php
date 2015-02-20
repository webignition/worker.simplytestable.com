<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\LinkIntegrity;

abstract class ExpectedOutputTest extends TaskDriverTest {

    abstract protected function getExpectedOutput();

    public function testTaskOutputMatchesExpectedOutput() {
        $this->assertEquals($this->getExpectedOutput(), json_decode($this->task->getOutput()->getOutput(), true));
    }
    
}
