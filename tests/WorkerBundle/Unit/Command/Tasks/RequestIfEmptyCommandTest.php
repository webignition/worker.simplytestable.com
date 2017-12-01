<?php

namespace Tests\WorkerBundle\Unit\Command\Tasks;

use Mockery\Mock;
use SimplyTestable\WorkerBundle\Command\Tasks\RequestIfEmptyCommand;
use SimplyTestable\WorkerBundle\Resque\Job\TasksRequestJob;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\WorkerBundle\Factory\MockFactory;
use webignition\ResqueJobFactory\ResqueJobFactory;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService as ResqueQueueService;

/**
 * @group Command/Tasks/RequestIfEmptyCommand
 */
class RequestIfEmptyCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider runDataProvider
     *
     * @param ResqueQueueService $resqueQueueService
     * @param ResqueJobFactory $resqueJobFactory
     * @throws \Exception
     */
    public function testRun(
        ResqueQueueService $resqueQueueService,
        ResqueJobFactory $resqueJobFactory
    ) {
        $command = $this->createRequestIfEmptyCommand([
            ResqueQueueService::class => $resqueQueueService,
            ResqueJobFactory::class => $resqueJobFactory,
        ]);

        $returnCode = $command->run(new ArrayInput([]), new NullOutput());

        $this->assertEquals(
            0,
            $returnCode
        );
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        /* @var Mock|TasksRequestJob $tasksRequestJob */
        $tasksRequestJob = \Mockery::mock(TasksRequestJob::class);

        return [
            'tasks-request queue is empty' => [
                'resqueQueueService' => MockFactory::createResqueQueueService([
                    'isEmpty' => [
                        'with' => 'tasks-request',
                        'return' => true,
                    ],
                    'enqueue' => [
                        'with' => $tasksRequestJob,
                    ],
                ]),
                'resqueJobFactory' => MockFactory::createResqueJobFactory([
                    'create' => [
                        'withArgs' => ['tasks-request'],
                        'return' => $tasksRequestJob,
                    ],
                ]),
            ],
            'tasks-request queue is not empty' => [
                'resqueQueueService' => MockFactory::createResqueQueueService([
                    'isEmpty' => [
                        'with' => 'tasks-request',
                        'return' => false,
                    ],
                ]),
                'resqueJobFactory' => MockFactory::createResqueJobFactory(),
            ],
        ];
    }

    /**
     * @param array $services
     *
     * @return RequestIfEmptyCommand
     */
    private function createRequestIfEmptyCommand($services = [])
    {
        if (!isset($services[ResqueQueueService::class])) {
            $services[ResqueQueueService::class] = MockFactory::createResqueQueueService();
        }

        if (!isset($services[ResqueJobFactory::class])) {
            $services[ResqueJobFactory::class] = MockFactory::createResqueJobFactory();
        }

        return new RequestIfEmptyCommand(
            $services[ResqueQueueService::class],
            $services[ResqueJobFactory::class]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
