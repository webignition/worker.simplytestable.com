<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task;

use Mockery\MockInterface;
use SimplyTestable\WorkerBundle\Command\Task\PerformCommand;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;

class PerformCommandTest extends ConsoleCommandBaseTestCase
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->clearRedis();
    }

    /**
     * @inheritdoc
     */
    protected function getAdditionalCommands()
    {
        return array(
            new PerformCommand()
        );
    }

    /**
     * @inheritdoc
     */
    protected static function getServicesToMock()
    {
        return [
            'logger',
            'simplytestable.services.taskservice',
        ];
    }

    public function testInvalidTask()
    {
        $returnCode = $this->executeCommand('simplytestable:task:perform', [
            'id' => 0,
        ]);

        $this->assertEquals(PerformCommand::RETURN_CODE_TASK_DOES_NOT_EXIST, $returnCode);
    }

    public function testMaintenanceMode()
    {
        $this->getWorkerService()->setReadOnly();

        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([]));

        $this->clearRedis();
        $returnCode = $this->executeCommand('simplytestable:task:perform', array(
            'id' => $task->getId()
        ));

        $this->assertEquals(PerformCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);
        $this->assertTrue($this->getRequeQueueService()->contains(
            'task-perform',
            [
                'id' => $task->getId()
            ]
        ));
    }

    public function testTaskServiceRaisesException()
    {
        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([]));

        /* @var TaskService|MockInterface $taskService */
        $taskService = $this->container->get('simplytestable.services.taskservice');
        $taskService
            ->shouldReceive('perform')
            ->with($task)
            ->andThrow(\Exception::class, 'message', 0);

        $returnCode = $this->executeCommand('simplytestable:task:perform', array(
            'id' => $task->getId()
        ));

        $this->assertEquals(PerformCommand::RETURN_CODE_TASK_SERVICE_RAISED_EXCEPTION, $returnCode);
    }

    /**
     * @dataProvider performDataProvider
     *
     * @param $taskValues
     * @param $taskServiceReturnValue
     * @param $expectedReturnCode
     */
    public function testPerform(
        $taskValues,
        $taskServiceReturnValue,
        $expectedReturnCode,
        $expectedResqueJobs,
        $expectedEmptyResqueQueues
    ) {
        $task = $this->getTaskFactory()->create($taskValues);

        /* @var TaskService|MockInterface $taskService */
        $taskService = $this->container->get('simplytestable.services.taskservice');
        $taskService
            ->shouldReceive('perform')
            ->with($task)
            ->andReturn($taskServiceReturnValue);

        $this->clearRedis();
        $returnCode = $this->executeCommand('simplytestable:task:perform', array(
            'id' => $task->getId()
        ));

        $this->assertEquals($expectedReturnCode, $returnCode);

        foreach ($expectedResqueJobs as $queueName => $data) {
            foreach ($data as $key => $value) {
                if ($value == '{{ taskId }}') {
                    $data[$key] = $task->getId();
                }
            }

            $this->assertFalse($this->getRequeQueueService()->isEmpty($queueName));
            $this->assertTrue($this->getRequeQueueService()->contains($queueName, $data));
        }

        foreach ($expectedEmptyResqueQueues as $queueName) {
            $this->assertTrue($this->getRequeQueueService()->isEmpty($queueName));
        }
    }

    /**
     * @return array
     */
    public function performDataProvider()
    {
        return [
            'task in wrong state' => [
                'taskValues' => TaskFactory::createTaskValuesFromDefaults([
                    'state' => TaskService::TASK_IN_PROGRESS_STATE,
                ]),
                'taskServiceReturnValue' => 1,
                'expectedReturnCode' => PerformCommand::RETURN_CODE_FAILED_DUE_TO_WRONG_STATE,
                'expectedResqueJobs' => [
                    'tasks-request' => [],
                ],
                'expectedEmptyResqueQueues' => [
                    'task-report-completion',
                ],
            ],
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
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->clearRedis();
        \Mockery::close();
    }
}
