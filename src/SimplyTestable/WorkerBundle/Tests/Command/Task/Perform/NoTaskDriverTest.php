<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform;

use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;

class NoTaskDriverTest extends PerformCommandTest {

    /**
     * @var int
     */
    private $commandReturnCode;


    public function setUp() {
        parent::setUp();

        $taskObject = $this->createTask('http://example.com/', 'HTML validation');
        $task = $this->getTaskService()->getById($taskObject->id);

        $unknownTaskType = new TaskType();
        $unknownTaskType->setName('test-foo');
        $unknownTaskType->setDescription('Description of unknown task type');
        $unknownTaskType->setClass($task->getType()->getClass());
        $this->getEntityManager()->persist($unknownTaskType);
        $this->getEntityManager()->flush();

        $task->setType($unknownTaskType);
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
        $this->assertEquals(-4, $this->commandReturnCode);
    }


}
