<?php

namespace Tests\AppBundle\Unit\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use SimplyTestable\AppBundle\Entity\Task\Task;
use SimplyTestable\AppBundle\Repository\TaskRepository;
use SimplyTestable\AppBundle\Services\CoreApplicationHttpClient;
use SimplyTestable\AppBundle\Services\StateService;
use SimplyTestable\AppBundle\Services\TaskService;
use SimplyTestable\AppBundle\Services\TaskTypeService;
use SimplyTestable\AppBundle\Services\WorkerService;
use Tests\AppBundle\Factory\MockEntityFactory;

/**
 * @group TaskService
 */
class TaskServiceTest extends \PHPUnit\Framework\TestCase
{
    const DEFAULT_TASK_URL = 'http://example.com/';
    const DEFAULT_TASK_PARAMETERS = '';
    const DEFAULT_TASK_TYPE = TaskTypeService::HTML_VALIDATION_NAME;
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
        $task->setState(MockEntityFactory::createState($stateName));

        $taskService = $this->createTaskService([
            StateService::class => $this->createStateService([
                Task::STATE_CANCELLED,
            ]),
        ]);

        $taskService->cancel($task);
        $this->assertEquals($expectedEndState, $task->getState()->getName());
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
        $task->setState(MockEntityFactory::createState($stateName));

        $stateService = \Mockery::mock(StateService::class);
        $stateService
            ->shouldReceive('fetch')
            ->with(Task::STATE_CANCELLED)
            ->andReturn(MockEntityFactory::createState(Task::STATE_CANCELLED));

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
            StateService::class => $stateService,
        ]);

        $taskService->cancel($task);
        $this->assertEquals($expectedEndState, $task->getState()->getName());
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

        if (!isset($services[StateService::class])) {
            $services[StateService::class] = \Mockery::mock(StateService::class);
        }

        if (!isset($services[WorkerService::class])) {
            $services[WorkerService::class] = \Mockery::mock(WorkerService::class);
        }

        if (!isset($services[CoreApplicationHttpClient::class])) {
            $services[CoreApplicationHttpClient::class] = \Mockery::mock(CoreApplicationHttpClient::class);
        }

        return new TaskService(
            $services[EntityManagerInterface::class],
            $services[LoggerInterface::class],
            $services[StateService::class],
            $services[WorkerService::class],
            $services[CoreApplicationHttpClient::class]
        );
    }

    /**
     * @param string[] $stateNamesToFetch
     *
     * @return MockInterface|StateService
     */
    private function createStateService($stateNamesToFetch)
    {
        $stateService = \Mockery::mock(StateService::class);

        foreach ($stateNamesToFetch as $stateName) {
            $stateService
                ->shouldReceive('fetch')
                ->with($stateName)
                ->andReturn(MockEntityFactory::createState($stateName));
        }

        return $stateService;
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
