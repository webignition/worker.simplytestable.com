<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform;

class InvalidStateTest extends PerformCommandTest {

    /**
     * @var int
     */
    private $commandReturnCode;


    public function setUp() {
        parent::setUp();

        $taskObject = $this->createTask('http://example.com/', 'HTML validation');

        $task = $this->getTaskService()->getById($taskObject->id);
        $task->setState($this->getTaskService()->getCompletedState());
        $this->getEntityManager()->persist($task);
        $this->getEntityManager()->flush();

        $this->commandReturnCode = $this->executeCommand('simplytestable:task:perform', array(
            'id' => $task->getId()
        ));
    }


    /**
     * @group standard
     */
    public function testReturnsStatusCode() {
        $this->assertEquals(-3, $this->commandReturnCode);
    }


}
