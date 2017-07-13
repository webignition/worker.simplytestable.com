<?php

namespace Tests\WorkerBundle\Functional\Command\Task;

use SimplyTestable\WorkerBundle\Command\Task\PerformEnqueueCommand;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Services\Resque\JobFactory;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;
use Tests\WorkerBundle\Factory\TaskFactory;
use Symfony\Component\Console\Input\ArrayInput;

class PerformEnqueueCommandTest extends BaseSimplyTestableTestCase
{
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

        $resqueQueueService = $this->container->get(QueueService::class);
        $resqueJobFactory = $this->container->get(JobFactory::class);

        $resqueQueueService->enqueue(
            $resqueJobFactory->create(
                'task-perform',
                ['id' => $tasks[0]->getId()]
            )
        );

        $command = $this->container->get(PerformEnqueueCommand::class);

        $returnCode = $command->execute(
            new ArrayInput([]),
            new StringOutput()
        );

        $this->assertEquals(0, $returnCode);

        foreach ($tasks as $task) {
            $this->assertTrue($resqueQueueService->contains('task-perform', array(
                'id' => $task->getId()
            )));
        }
    }
}
