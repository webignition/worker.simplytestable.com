<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Tasks\Request;

class WorkerMaintenanceModeTest extends RequestCommandTest {

    public function setUp() {
        parent::setUp();
        $this->getWorkerService()->setReadOnly();
        $this->clearRedis();
    }

    public function testReturnStatusCode() {
        $this->assertEquals(2, $this->executeCommand('simplytestable:tasks:request'));
    }

    public function testReturnsResqueJobToQueue() {
        // TODO: this
    }
}
