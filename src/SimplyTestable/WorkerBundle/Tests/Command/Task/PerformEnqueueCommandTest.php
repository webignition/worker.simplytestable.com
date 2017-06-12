<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task;

use SimplyTestable\WorkerBundle\Command\Task\PerformEnqueueCommand;
use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;

class PerformEnqueueCommandTest extends ConsoleCommandBaseTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAdditionalCommands()
    {
        return array(
            new PerformEnqueueCommand()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->clearRedis();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->clearRedis();
        parent::tearDown();
    }

    public function testEnqueueTaskPerformJobs()
    {
        $taskPropertyCollection = [
            [
                'url' => 'http://example.com/1/',
                'type' => 'HTML validation'
            ],
            [
                'url' => 'http://example.com/2/',
                'type' => 'HTML validation'
            ],
            [
                'url' => 'http://example.com/3/',
                'type' => 'HTML validation'
            ],
        ];

        $tasks = array();
        foreach ($taskPropertyCollection as $taskProperties) {
            $tasks[] = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults(
                $taskProperties
            ));
        }

        $this->clearRedis();

        $this->getResqueQueueService()->enqueue(
            $this->getResqueJobFactoryService()->create(
                'task-perform',
                ['id' => $tasks[0]->getId()]
            )
        );

        $this->assertEquals(0, $this->executeCommand('simplytestable:task:perform:enqueue'));

        foreach ($tasks as $task) {
            $this->assertTrue($this->getResqueQueueService()->contains('task-perform', array(
                'id' => $task->getId()
            )));
        }
    }
}
