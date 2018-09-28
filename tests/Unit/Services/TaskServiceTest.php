<?php

namespace App\Tests\Unit\Services;

use App\Model\Task\TypeInterface;
use App\Services\TaskTypeFactory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use App\Entity\Task\Task;
use App\Repository\TaskRepository;
use App\Services\CoreApplicationHttpClient;
use App\Services\TaskService;
use App\Services\WorkerService;

/**
 * @group TaskService
 */
class TaskServiceTest extends \PHPUnit\Framework\TestCase
{
    const DEFAULT_TASK_URL = 'http://example.com/';
    const DEFAULT_TASK_PARAMETERS = '';
    const DEFAULT_TASK_TYPE = TypeInterface::TYPE_HTML_VALIDATION;
    const DEFAULT_TASK_STATE = Task::STATE_QUEUED;

    /**
     * @dataProvider cancelNoStageChangeDataProvider
     *
     * @param string $stateName
     * @param string $expectedEndState
     */
    public function testCancelNoStateChange($stateName, $expectedEndState)
    {
        $task = new Task();
        $task->setState($stateName);

        $taskService = $this->createTaskService();

        $taskService->cancel($task);
        $this->assertEquals($expectedEndState, $task->getState());
    }

    /**
     * @return array
     */
    public function cancelNoStageChangeDataProvider()
    {
        return [
            'state: cancelled' => [
                'stateName' => Task::STATE_CANCELLED,
                'expectedEndState' => Task::STATE_CANCELLED,
            ],
            'state: completed' => [
                'stateName' => Task::STATE_COMPLETED,
                'expectedEndState' => Task::STATE_COMPLETED,
            ],
        ];
    }

    /**
     * @dataProvider cancelDataProvider
     *
     * @param string $stateName
     * @param string $expectedEndState
     */
    public function testCancel($stateName, $expectedEndState)
    {
        $task = new Task();
        $task->setState($stateName);

        $taskRepository = \Mockery::mock(TaskRepository::class);

        $entityManager = \Mockery::mock(EntityManager::class);
        $entityManager
            ->shouldReceive('getRepository')
            ->with(Task::class)
            ->andReturn($taskRepository);

        $entityManager
            ->shouldReceive('persist')
            ->with($task);
        $entityManager
            ->shouldReceive('flush');

        $taskService = $this->createTaskService([
            EntityManagerInterface::class => $entityManager,
        ]);

        $taskService->cancel($task);
        $this->assertEquals($expectedEndState, $task->getState());
    }

    /**
     * @return array
     */
    public function cancelDataProvider()
    {
        return [
            'state: queued' => [
                'stateName' => Task::STATE_QUEUED,
                'expectedEndState' => Task::STATE_CANCELLED,
            ],
            'state: in-progress' => [
                'stateName' => Task::STATE_IN_PROGRESS,
                'expectedEndState' => Task::STATE_CANCELLED,
            ],
        ];
    }

    /**
     * @param array $services
     *
     * @return TaskService
     */
    private function createTaskService($services = [])
    {
        if (!isset($services[EntityManagerInterface::class])) {
            $taskRepository = \Mockery::mock(TaskRepository::class);

            $entityManager = \Mockery::mock(EntityManagerInterface::class);
            $entityManager
                ->shouldReceive('getRepository')
                ->with(Task::class)
                ->andReturn($taskRepository);

            $services[EntityManagerInterface::class] = $entityManager;
        }

        if (!isset($services[LoggerInterface::class])) {
            $services[LoggerInterface::class] = \Mockery::mock(LoggerInterface::class);
        }

        if (!isset($services[WorkerService::class])) {
            $services[WorkerService::class] = \Mockery::mock(WorkerService::class);
        }

        if (!isset($services[CoreApplicationHttpClient::class])) {
            $services[CoreApplicationHttpClient::class] = \Mockery::mock(CoreApplicationHttpClient::class);
        }

        if (!isset($services[TaskTypeFactory::class])) {
            $services[TaskTypeFactory::class] = new TaskTypeFactory();
        }

        return new TaskService(
            $services[EntityManagerInterface::class],
            $services[LoggerInterface::class],
            $services[WorkerService::class],
            $services[CoreApplicationHttpClient::class],
            $services[TaskTypeFactory::class]
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
