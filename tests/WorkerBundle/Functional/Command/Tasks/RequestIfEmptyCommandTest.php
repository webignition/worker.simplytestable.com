<?php

namespace Tests\WorkerBundle\Functional\Command\Tasks;

use SimplyTestable\WorkerBundle\Command\Tasks\RequestIfEmptyCommand;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Services\Resque\JobFactory;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;

class RequestIfEmptyCommandTest extends BaseSimplyTestableTestCase
{
    /**
     * @dataProvider runDataProvider
     *
     * @param bool $hasEmptyQueue
     */
    public function testRun($hasEmptyQueue)
    {
        $this->clearRedis();

        $resqueQueueService = $this->container->get(QueueService::class);
        $resqueJobFactory = $this->container->get(JobFactory::class);

        if (!$hasEmptyQueue) {
            $resqueQueueService->enqueue(
                $resqueJobFactory->create(
                    'tasks-request'
                )
            );
        }

        $command = $this->container->get(RequestIfEmptyCommand::class);
        $returnCode = $command->run(new ArrayInput([]), new StringOutput());

        $this->assertEquals(
            0,
            $returnCode
        );

        $this->assertFalse($resqueQueueService->isEmpty('tasks-request'));
        $this->assertEquals(1, $resqueQueueService->getQueueLength('tasks-request'));
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        return [
            'with empty queue' => [
                'hasEmptyQueue' => true,
            ],
            'with non-empty queue' => [
                'hasEmptyQueue' => false,
            ],
        ];
    }
}
