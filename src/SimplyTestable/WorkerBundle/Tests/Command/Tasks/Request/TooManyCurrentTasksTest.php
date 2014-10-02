<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Tasks\Request;

class TooManyCurrentTasksTest extends RequestCommandTest {

    /**
     * @var int
     */
    private $returnCode;

    public function setUp() {
        parent::setUp();
        $this->clearRedis();

        for ($index = 0; $index < ($this->getTasksService()->getWorkerProcessCount() * $this->getTasksService()->getMaxTasksRequestFactor()); $index++) {
            $this->createTask('http://foo.example.com/' . $index, 'HTML Validation');
        }

        $this->returnCode = $this->executeCommand('simplytestable:tasks:request');
    }

    public function testReturnStatusCode() {
        $this->assertEquals(1, $this->returnCode);
    }

    public function testReturnsResqueJobToQueue() {
        $this->assertTrue($this->getRequeQueueService()->contains('tasks-request'));
    }
}
