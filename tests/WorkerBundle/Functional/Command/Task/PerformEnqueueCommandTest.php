<?php

namespace Tests\WorkerBundle\Functional\Command\Task;

use SimplyTestable\WorkerBundle\Command\Task\PerformEnqueueCommand;
use SimplyTestable\WorkerBundle\Resque\Job\TaskPerformJob;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService;
use Symfony\Component\Console\Output\NullOutput;
use Tests\WorkerBundle\Factory\TestTaskFactory;
use Tests\WorkerBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;

class PerformEnqueueCommandTest extends AbstractBaseTestCase
{
    /**
     * @throws \CredisException
     * @throws \Exception
     */
    public function testEnqueueTaskPerformJobs()
    {
        $testTaskFactory = new TestTaskFactory(self::$container);

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
            $tasks[] = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults(
                $taskProperties
            ));
        }

        $this->clearRedis();

        $resqueQueueService = self::$container->get(QueueService::class);
        $resqueQueueService->enqueue(new TaskPerformJob(['id' => $tasks[0]->getId()]));

        $command = self::$container->get(PerformEnqueueCommand::class);

        $returnCode = $command->execute(
            new ArrayInput([]),
            new NullOutput()
        );

        $this->assertEquals(0, $returnCode);

        foreach ($tasks as $task) {
            $this->assertTrue($resqueQueueService->contains('task-perform', array(
                'id' => $task->getId()
            )));
        }
    }
}
