<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Tasks\Request;

class SuccessTest extends RequestCommandTest {

    public function setUp() {
        parent::setUp();
        $this->clearRedis();

        $this->setHttpFixtures($this->buildHttpFixtureSet([
            'HTTP/1.1 200'
        ]));
    }

    public function testReturnStatusCode() {
        $this->assertEquals(0, $this->executeCommand('simplytestable:tasks:request'));
    }

    public function testResqueJobToQueueIsEmpty() {
        $this->assertFalse($this->getRequeQueueService()->contains('tasks-request'));
    }
}
