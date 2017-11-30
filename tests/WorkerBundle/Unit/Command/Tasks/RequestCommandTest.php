<?php

namespace Tests\WorkerBundle\Unit\Command\Tasks;

use Mockery\Mock;
use SimplyTestable\WorkerBundle\Command\Tasks\RequestCommand;
use SimplyTestable\WorkerBundle\Resque\Job\TasksRequestJob;
use SimplyTestable\WorkerBundle\Services\TasksService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Symfony\Component\Console\Output\NullOutput;
use Tests\WorkerBundle\Factory\MockFactory;
use Symfony\Component\Console\Input\ArrayInput;
use SimplyTestable\WorkerBundle\Services\Resque\JobFactory as ResqueJobFactory;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService as ResqueQueueService;

/**
 * @group Command/Tasks/RequestCommand
 */
class RequestCommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider maintenanceModeDataProvider
     *
     * @param ResqueQueueService $resqueQueueService
     * @param ResqueJobFactory $resqueJobFactory
     *
     * @throws \Exception
     */
    public function testMaintenanceMode(
        ResqueQueueService $resqueQueueService,
        ResqueJobFactory $resqueJobFactory
    ) {
        $command = $this->createRequestCommand([
            WorkerService::class => MockFactory::createWorkerService([
                'isMaintenanceReadOnly' => [
                    'return' => true,
                ],
            ]),
            ResqueQueueService::class => $resqueQueueService,
            ResqueJobFactory::class => $resqueJobFactory,
        ]);

        $returnCode = $command->run(new ArrayInput([]), new NullOutput());

        $this->assertEquals(
            RequestCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }

    public function maintenanceModeDataProvider()
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
     * @return RequestCommand
     */
    private function createRequestCommand($services = [])
    {
        if (!isset($services[TasksService::class])) {
            $services[TasksService::class] = MockFactory::createTasksService();
        }

        if (!isset($services[WorkerService::class])) {
            $services[WorkerService::class] = MockFactory::createWorkerService();
        }

        if (!isset($services[ResqueQueueService::class])) {
            $services[ResqueQueueService::class] = MockFactory::createResqueQueueService();
        }

        if (!isset($services[ResqueJobFactory::class])) {
            $services[ResqueJobFactory::class] = MockFactory::createResqueJobFactory();
        }

        return new RequestCommand(
            $services[TasksService::class],
            $services[WorkerService::class],
            $services[ResqueQueueService::class],
            $services[ResqueJobFactory::class]
        );
    }
}
