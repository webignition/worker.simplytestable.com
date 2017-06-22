<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Command\Task;

use SimplyTestable\WorkerBundle\Command\Task\PerformEnqueueCommand;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;
use Symfony\Component\Console\Input\ArrayInput;

class PerformEnqueueCommandTest extends BaseSimplyTestableTestCase
{
    public function testGetAsService()
    {
        $this->assertInstanceOf(
            PerformEnqueueCommand::class,
            $this->container->get('simplytestable.command.task.performenqueue')
        );
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
            $this->getResqueJobFactory()->create(
                'task-perform',
                ['id' => $tasks[0]->getId()]
            )
        );

        $command = $this->createPerformEnqueueCommand();

        $returnCode = $command->execute(
            new ArrayInput([]),
            new StringOutput()
        );

        $this->assertEquals(0, $returnCode);

        foreach ($tasks as $task) {
            $this->assertTrue($this->getResqueQueueService()->contains('task-perform', array(
                'id' => $task->getId()
            )));
        }
    }

    /**
     * @return PerformEnqueueCommand
     */
    private function createPerformEnqueueCommand()
    {
        return new PerformEnqueueCommand(
            $this->container->get('simplytestable.services.taskservice'),
            $this->container->get('simplytestable.services.resque.queueservice'),
            $this->container->get('simplytestable.services.resque.jobfactory')
        );
    }
}
