<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task\Perform;

use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;

class PerformCommandTest extends ConsoleCommandBaseTestCase {
    
    public function setUp() {
        parent::setUp();
        $this->removeAllTasks();
        $this->removeAllTestTaskTypes(); 
    }

    public function tearDown() {
        $this->clearRedis();
        parent::tearDown();
    }
    
    protected function getAdditionalCommands() {
        return array(
            new \SimplyTestable\WorkerBundle\Command\Task\PerformCommand()
        );
    }     

    /**
     * @group standard
     */
    public function testPerformInMaintenanceReadOnlyModeReturnsStatusCodeMinus1() {
        $task = $this->createTask('http://example.com/', 'HTML validation');
        $this->getWorkerService()->setReadOnly();

        $this->assertEquals(-1, $this->executeCommand('simplytestable:task:perform', array(
            'id' => $task->id
        )));
    }


    /**
     * @group standard
     */
    public function testPerformInMaintenanceReadOnlyModeReturnsResqueJobToQueue() {
        $this->clearRedis();

        $task = $this->createTask('http://example.com/', 'HTML validation');

        $this->clearRedis();

        $this->getWorkerService()->setReadOnly();

        $this->executeCommand('simplytestable:task:perform', array(
            'id' => $task->id
        ));

        $this->assertTrue($this->getRequeQueueService()->contains(
            'task-perform', ['id' => $task->id]
        ));
    }


    /**
     * @group standard
     */
    public function testPerformInvalidTestReturnsStatusCodeMinus2() {
        $this->assertEquals(-2, $this->executeCommand('simplytestable:task:perform', array(
            'id' => -1
        )));
    }



    /**
     * @group standard
     */
    public function testPerformForTestInInvalidStateReturnsStatusCodeMinus3() {
        $taskObject = $this->createTask('http://example.com/', 'HTML validation');

        $task = $this->getTaskService()->getById($taskObject->id);
        $task->setState($this->getTaskService()->getCompletedState());
        $this->getEntityManager()->persist($task);
        $this->getEntityManager()->flush();

        $this->assertEquals(-3, $this->executeCommand('simplytestable:task:perform', array(
            'id' => $task->getId()
        )));
    }


    /**
     * @group standard
     */
    public function testPerformTestWhereNoTaskDriverFoundReturnsStatusCodeMinus4() {
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

        $this->assertEquals(-4, $this->executeCommand('simplytestable:task:perform', array(
            'id' => $task->getId()
        )));
    }


    public function testPerformedTaskPlacesReportCompletionJobInResqueQueue() {
        $this->container->get('simplytestable.services.htmlValidatorWrapperService')->loadFixturesFromPath(
            $this->getFixturesDataPath(__FUNCTION__ . '/HtmlValidatorResponses')
        );

        $task = $this->createTask('http://example.com/', 'HTML validation');

        $this->assertEquals(0, $this->executeCommand('simplytestable:task:perform', array(
            'id' => $task->id
        )));

        $this->assertTrue($this->getRequeQueueService()->contains(
            'task-report-completion', ['id' => $task->id]
        ));
    }
}
