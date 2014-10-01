<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform;

class MaintenanceReadOnlyModeTest extends PerformCommandTest {

    /**
     * @var int
     */
    private $commandReturnCode;


    /**
     * @var \stdClass
     */
    private $task;

    public function setUp() {
        parent::setUp();

        $this->task = $this->createTask('http://example.com/', 'HTML validation');
        $this->getWorkerService()->setReadOnly();

        $this->clearRedis();

        $this->commandReturnCode = $this->executeCommand('simplytestable:task:perform', array(
            'id' => $this->task->id
        ));
    }


    /**
     * @group standard
     */
    public function testReturnsStatusCode() {
        $this->assertEquals(-1, $this->commandReturnCode);
    }


    /**
     * @group standard
     */
    public function testReturnsResqueJobToQueue() {
        $this->assertTrue($this->getRequeQueueService()->contains(
            'task-perform', ['id' => $this->task->id]
        ));
    }



}
