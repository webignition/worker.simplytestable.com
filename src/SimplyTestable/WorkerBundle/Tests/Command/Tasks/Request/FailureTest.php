<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Tasks\Request;

abstract class FailureTest extends RequestCommandTest {

    public function setUp() {
        parent::setUp();
        $this->clearRedis();
    }

    public function testReturnStatusCode() {
        $this->assertEquals(1, $this->executeCommand('simplytestable:tasks:request'));
    }

    public function testReturnsResqueJobToQueue() {
        $this->executeCommand('simplytestable:tasks:request');
        $this->assertTrue($this->getRequeQueueService()->contains('tasks-request'));
    }
}
