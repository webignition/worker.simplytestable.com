<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Command\Task;

use Mockery\MockInterface;
use SimplyTestable\WorkerBundle\Command\Task\PerformCommand;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;
use SimplyTestable\WorkerBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;

class PerformCommandTest extends BaseSimplyTestableTestCase
{
    /**
     * {@inheritdoc}
     */
    protected static function getServicesToMock()
    {
        return [
            'simplytestable.services.taskservice',
        ];
    }

    public function testGetAsService()
    {
        $this->assertInstanceOf(
            PerformCommand::class,
            $this->container->get('simplytestable.command.task.perform')
        );
    }

    public function testRunWithInvalidTask()
    {
        $command = $this->createPerformCommand();

        $this->assertEquals(
            PerformCommand::RETURN_CODE_TASK_DOES_NOT_EXIST,
            $command->run(
                new ArrayInput([
                    'id' => 0,
                ]),
                new StringOutput()
            )
        );
    }

    public function testRunInMaintenanceReadOnlyMode()
    {
        $this->getWorkerService()->setReadOnly();
        $this->clearRedis();
        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([]));

        $command = $this->createPerformCommand();
        $returnCode = $command->run(
            new ArrayInput([
                'id' => $task->getId(),
            ]),
            new StringOutput()
        );

        $this->assertEquals(PerformCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);
        $this->assertTrue($this->getResqueQueueService()->contains(
            'task-perform',
            [
                'id' => $task->getId()
            ]
        ));
    }

    public function testTaskServiceRaisesException()
    {
        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([]));
        $this->createTaskServiceMock($task, new \Exception());

        $command = $this->createPerformCommand();
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
     */
    private function createTaskServiceMock(Task $task, $performResult)
    {
        /* @var TaskService|MockInterface $taskService */
        $taskService = $this->container->get('simplytestable.services.taskservice');

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
    }

    /**
     * @return PerformCommand
     */
    private function createPerformCommand()
    {
        return new PerformCommand(
            $this->container->get('logger'),
            $this->container->get('simplytestable.services.taskservice'),
            $this->container->get('simplytestable.services.workerservice'),
            $this->container->get('simplytestable.services.resque.queueservice'),
            $this->container->get('simplytestable.services.resque.jobfactory')
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
