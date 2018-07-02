<?php

namespace Tests\WorkerBundle\Unit\Command\Tasks;

use Mockery\MockInterface;
use SimplyTestable\WorkerBundle\Command\Tasks\RequestCommand;
use SimplyTestable\WorkerBundle\Resque\Job\TasksRequestJob;
use SimplyTestable\WorkerBundle\Services\TasksService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Symfony\Component\Console\Output\NullOutput;
use Tests\WorkerBundle\Factory\MockFactory;
use Symfony\Component\Console\Input\ArrayInput;
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
     *
     * @throws \Exception
     */
    public function testMaintenanceMode(ResqueQueueService $resqueQueueService)
    {
        $command = $this->createRequestCommand([
            WorkerService::class => MockFactory::createWorkerService([
                'isMaintenanceReadOnly' => [
                    'return' => true,
                ],
            ]),
            ResqueQueueService::class => $resqueQueueService,
        ]);

        $returnCode = $command->run(new ArrayInput([]), new NullOutput());

        $this->assertEquals(
            RequestCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }

    /**
     * @return array
     */
    public function maintenanceModeDataProvider()
    {
        return [
            'tasks-request queue is empty' => [
                'resqueQueueService' => $this->createResqueQueueServiceWithEnqueueCall(),
            ],
            'tasks-request queue is not empty' => [
                'resqueQueueService' => $this->createResqueQueueService(false)
            ],
        ];
    }

    /**
     * @return MockInterface|ResqueQueueService
     */
    private function createResqueQueueServiceWithEnqueueCall()
    {
        $resqueQueueService = $this->createResqueQueueService(true);

        $resqueQueueService
            ->shouldReceive('enqueue')
            ->withArgs(function (TasksRequestJob $tasksRequestJob) {
                $this->assertInstanceOf(TasksRequestJob::class, $tasksRequestJob);
                return true;
            });

        return $resqueQueueService;
    }

    /**
     * @param bool $isEmpty
     *
     * @return MockInterface|ResqueQueueService
     */
    private function createResqueQueueService($isEmpty)
    {
        $resqueQueueService = \Mockery::mock(ResqueQueueService::class);

        $resqueQueueService
            ->shouldReceive('isEmpty')
            ->with('tasks-request')
            ->andReturn($isEmpty);

        return $resqueQueueService;
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

        return new RequestCommand(
            $services[TasksService::class],
            $services[WorkerService::class],
            $services[ResqueQueueService::class]
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
