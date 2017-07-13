<?php

namespace Tests\WorkerBundle\Unit\Command\Task;

use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use SimplyTestable\WorkerBundle\Command\Task\PerformCommand;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Services\Resque\JobFactory;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Symfony\Component\Console\Input\ArrayInput;
use Tests\WorkerBundle\Factory\MockEntityFactory;

class PerformCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testTaskServiceRaisesException()
    {
        $task = MockEntityFactory::createTask(
            1,
            MockEntityFactory::createState(TaskService::TASK_STARTING_STATE)
        );

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('info');
        $logger
            ->shouldReceive('error');

        $workerService = \Mockery::mock(WorkerService::class);
        $workerService
            ->shouldReceive('isMaintenanceReadOnly')
            ->andReturn(false);

        $command = $this->createPerformCommand([
            'taskService' => $this->createTaskServiceMock($task, new \Exception()),
            'logger' => $logger,
            'workerService' => $workerService,
        ]);

        $returnCode = $command->run(
            new ArrayInput([
                'id' => $task->getId(),
            ]),
            new StringOutput()
        );

        $this->assertEquals(PerformCommand::RETURN_CODE_TASK_SERVICE_RAISED_EXCEPTION, $returnCode);
    }

    /**
     * @dataProvider runDataProvider
     *
     * @param array $taskValues
     * @param int $taskServiceReturnValue
     * @param int $expectedReturnCode
     * @param array $expectedResqueJobs
     * @param array $expectedEmptyResqueQueues
     */
    public function testRun(
        $taskValues,
        $taskServiceReturnValue,
        $expectedReturnCode,
        $expectedResqueJobs,
        $expectedEmptyResqueQueues
    ) {
        $task = $this->getTaskFactory()->create($taskValues);
        $this->clearRedis();
        $this->createTaskServiceMock($task, $taskServiceReturnValue);

        $command = $this->createPerformCommand();
        $returnCode = $command->run(
            new ArrayInput([
                'id' => $task->getId(),
            ]),
            new StringOutput()
        );

        $this->assertEquals($expectedReturnCode, $returnCode);

        foreach ($expectedResqueJobs as $queueName => $data) {
            foreach ($data as $key => $value) {
                if ($value == '{{ taskId }}') {
                    $data[$key] = $task->getId();
                }
            }

            $this->assertFalse($this->getResqueQueueService()->isEmpty($queueName));
            $this->assertTrue($this->getResqueQueueService()->contains($queueName, $data));
        }

        foreach ($expectedEmptyResqueQueues as $queueName) {
            $this->assertTrue($this->getResqueQueueService()->isEmpty($queueName));
        }
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        return [
            'unknown error' => [
                'taskValues' => TaskFactory::createTaskValuesFromDefaults([
                    'state' => TaskService::TASK_IN_PROGRESS_STATE,
                ]),
                'taskServiceReturnValue' => 99,
                'expectedReturnCode' => PerformCommand::RETURN_CODE_UNKNOWN_ERROR,
                'expectedResqueJobs' => [
                    'tasks-request' => [],
                ],
                'expectedEmptyResqueQueues' => [
                    'task-report-completion',
                ],
            ],
            'success' => [
                'taskValues' => TaskFactory::createTaskValuesFromDefaults([
                    'state' => TaskService::TASK_IN_PROGRESS_STATE,
                ]),
                'taskServiceReturnValue' => 0,
                'expectedReturnCode' => 0,
                'expectedResqueJobs' => [
                    'tasks-request' => [],
                    'task-report-completion' => [
                        'id' => '{{ taskId }}',
                    ],
                ],
                'expectedEmptyResqueQueues' => [],
            ],
        ];
    }

    /**
     * @param Task $task
     * @param mixed $performResult
     *
     * @return MockInterface|TaskService
     */
    private function createTaskServiceMock(Task $task, $performResult)
    {
        /* @var TaskService|MockInterface $taskService */
        $taskService = \Mockery::mock(TaskService::class);

        $taskService
            ->shouldReceive('getById')
            ->with($task->getId())
            ->andReturn($task);

        if ($performResult instanceof \Exception) {
            $taskService
                ->shouldReceive('perform')
                ->with($task)
                ->andThrow(\Exception::class);
        } else {
            $taskService
                ->shouldReceive('perform')
                ->with($task)
                ->andReturn($performResult);
        }

        return $taskService;
    }

    /**
     * @param array $services
     *
     * @return PerformCommand
     */
    private function createPerformCommand($services = [])
    {
        if (!isset($services['logger'])) {
            $services['logger'] = \Mockery::mock(LoggerInterface::class);
        }

        if (!isset($services['taskService'])) {
            $services['taskService'] = \Mockery::mock(TaskService::class);
        }

        if (!isset($services['resqueQueueService'])) {
            $services['resqueQueueService'] = \Mockery::mock(QueueService::class);
        }

        if (!isset($services['resqueJobFactory'])) {
            $services['resqueJobFactory'] = \Mockery::mock(JobFactory::class);
        }

        if (!isset($services['workerService'])) {
            $services['workerService'] = \Mockery::mock(WorkerService::class);
        }

        return new PerformCommand(
            $services['logger'],
            $services['taskService'],
            $services['workerService'],
            $services['resqueQueueService'],
            $services['resqueJobFactory']
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
