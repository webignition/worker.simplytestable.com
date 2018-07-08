<?php

namespace Tests\WorkerBundle\Functional\Command\Task;

use SimplyTestable\WorkerBundle\Command\Task\PerformCommand;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Symfony\Component\Console\Output\NullOutput;
use Tests\WorkerBundle\Factory\TestTaskFactory;
use Tests\WorkerBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * @group Command/Task/PerformCommand
 */
class PerformCommandTest extends AbstractBaseTestCase
{
    /**
     * @var PerformCommand
     */
    private $command;

    protected function setUp()
    {
        parent::setUp();

        $this->command = $this->container->get(PerformCommand::class);
    }

    /**
     * @throws \Exception
     */
    public function testRunInMaintenanceReadOnlyMode()
    {
        $this->container->get(WorkerService::class)->setReadOnly();
        $this->clearRedis();

        $testTaskFactory = new TestTaskFactory($this->container);
        $task = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([]));

        $returnCode = $this->command->run(
            new ArrayInput([
                'id' => $task->getId(),
            ]),
            new NullOutput()
        );

        $this->assertEquals(PerformCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);
        $this->assertTrue($this->container->get(QueueService::class)->contains(
            'task-perform',
            [
                'id' => $task->getId()
            ]
        ));
    }

    /**
     * @throws \Exception
     */
    public function testTaskServiceRaisesException()
    {
        $testTaskFactory = new TestTaskFactory($this->container);
        $task = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([]));

        $taskService = $this->container->get(TaskService::class);
        $taskService->setPerformException(new \Exception());

        $returnCode = $this->command->run(
            new ArrayInput([
                'id' => $task->getId(),
            ]),
            new NullOutput()
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
     *
     * @throws \Exception
     */
    public function testRun(
        $taskValues,
        $taskServiceReturnValue,
        $expectedReturnCode,
        $expectedResqueJobs,
        $expectedEmptyResqueQueues
    ) {
        $testTaskFactory = new TestTaskFactory($this->container);
        $task = $testTaskFactory->create($taskValues);
        $this->clearRedis();

        $taskService = $this->container->get(TaskService::class);
        $taskService->setPerformResult($taskServiceReturnValue);

        $returnCode = $this->command->run(
            new ArrayInput([
                'id' => $task->getId(),
            ]),
            new NullOutput()
        );

        $this->assertEquals($expectedReturnCode, $returnCode);

        $resqueQueueService = $this->container->get(QueueService::class);

        foreach ($expectedResqueJobs as $queueName => $data) {
            foreach ($data as $key => $value) {
                if ($value == '{{ taskId }}') {
                    $data[$key] = $task->getId();
                }
            }

            $this->assertFalse($resqueQueueService->isEmpty($queueName));
            $this->assertTrue($resqueQueueService->contains($queueName, $data));
        }

        foreach ($expectedEmptyResqueQueues as $queueName) {
            $this->assertTrue($resqueQueueService->isEmpty($queueName));
        }
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        return [
            'unknown error' => [
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'state' => Task::STATE_IN_PROGRESS,
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
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'state' => Task::STATE_IN_PROGRESS,
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
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
