<?php

namespace Tests\WorkerBundle\Unit\Services;

use Doctrine\ORM\EntityManager;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use SimplyTestable\WorkerBundle\Entity\State;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Repository\TaskRepository;
use SimplyTestable\WorkerBundle\Services\CoreApplicationRouter;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\StateService;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use SimplyTestable\WorkerBundle\Services\UrlService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Tests\WorkerBundle\Factory\StateFactory;
use Tests\WorkerBundle\Factory\TaskFactory;
use Tests\WorkerBundle\Factory\TaskTypeFactory;

class TaskServiceTest extends \PHPUnit_Framework_TestCase
{
    const DEFAULT_TASK_URL = 'http://example.com/';
    const DEFAULT_TASK_PARAMETERS = '';
    const DEFAULT_TASK_TYPE = TaskTypeService::HTML_VALIDATION_NAME;
    const DEFAULT_TASK_STATE = TaskService::TASK_STARTING_STATE;

    /**
     * @dataProvider cancelNoStageChangeDataProvider
     *
     * @param string $stateName
     * @param string $expectedEndState
     */
    public function testCancelNoStateChange($stateName, $expectedEndState)
    {
        $task = new Task();
        $task->setState(StateFactory::create($stateName));

        $taskService = $this->createTaskService([
            'stateService' => $this->createStateService([
                TaskService::TASK_CANCELLED_STATE,
            ]),
        ]);

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
                'stateName' => TaskService::TASK_CANCELLED_STATE,
                'expectedEndState' => TaskService::TASK_CANCELLED_STATE,
            ],
            'state: completed' => [
                'stateName' => TaskService::TASK_COMPLETED_STATE,
                'expectedEndState' => TaskService::TASK_COMPLETED_STATE,
            ],
        ];
    }

    /**
     * @dataProvider cancelDataProvider
     *
     * @param string $stateName
     * @param string $expectedEndState
     */
    public function testCancelFoo($stateName, $expectedEndState)
    {
        $task = new Task();
        $task->setState(StateFactory::create($stateName));

        $stateService = \Mockery::mock(StateService::class);
        $stateService
            ->shouldReceive('fetch')
            ->with(TaskService::TASK_CANCELLED_STATE)
            ->andReturn(StateFactory::create(TaskService::TASK_CANCELLED_STATE));

        $entityManager = \Mockery::mock(EntityManager::class);
        $entityManager
            ->shouldReceive('persist')
            ->with($task);
        $entityManager
            ->shouldReceive('flush');

        $taskService = $this->createTaskService([
            'entityManager' => $entityManager,
            'stateService' => $stateService,
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
                'stateName' => TaskService::TASK_STARTING_STATE,
                'expectedEndState' => TaskService::TASK_CANCELLED_STATE,
            ],
            'state: in-progress' => [
                'stateName' => TaskService::TASK_IN_PROGRESS_STATE,
                'expectedEndState' => TaskService::TASK_CANCELLED_STATE,
            ],
        ];
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param $url
     * @param $taskTypeName
     * @param $parameters
     */
    public function testCreate($url, $taskTypeName, $parameters)
    {
        $taskType = TaskTypeFactory::create($taskTypeName);
        $taskStartingState = TaskService::TASK_STARTING_STATE;

        $stateService = \Mockery::mock(StateService::class);
        $stateService
            ->shouldReceive('fetch')
            ->with(TaskService::TASK_STARTING_STATE)
            ->andReturn(StateFactory::create($taskStartingState));

        $taskRepository = \Mockery::mock(TaskRepository::class);
        $taskRepository
            ->shouldReceive('findOneBy')
            ->with([
                'state' => $taskStartingState,
                'type' => $taskType,
                'url' => $url,
            ])
            ->andReturn(null);

        $entityManager = \Mockery::mock(EntityManager::class);
        $entityManager
            ->shouldReceive('getRepository')
            ->with(Task::class)
            ->andReturn($taskRepository);

        $taskService = $this->createTaskService([
            'entityManager' => $entityManager,
            'stateService' => $stateService,
        ]);
        $task = $taskService->create($url, $taskType, $parameters);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals(TaskService::TASK_STARTING_STATE, $task->getState());
        $this->assertEquals($url, $task->getUrl());
        $this->assertEquals(strtolower($taskTypeName), strtolower($task->getType()));
        $this->assertEquals($parameters, $task->getParameters());
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'html validation default' => [
                'url' => self::DEFAULT_TASK_URL,
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_NAME,
                'parameters' => self::DEFAULT_TASK_PARAMETERS,
            ],
            'css validation default' => [
                'url' => self::DEFAULT_TASK_URL,
                'taskTypeName' => TaskTypeService::CSS_VALIDATION_NAME,
                'parameters' => self::DEFAULT_TASK_PARAMETERS,
            ],
            'js static analysis default' => [
                'url' => self::DEFAULT_TASK_URL,
                'taskTypeName' => TaskTypeService::JS_STATIC_ANALYSIS_NAME,
                'parameters' => self::DEFAULT_TASK_PARAMETERS,
            ],
            'link integrity default' => [
                'url' => self::DEFAULT_TASK_URL,
                'taskTypeName' => TaskTypeService::LINK_INTEGRITY_NAME,
                'parameters' => self::DEFAULT_TASK_PARAMETERS,
            ],
            'url discovery default' => [
                'url' => self::DEFAULT_TASK_URL,
                'taskTypeName' => TaskTypeService::URL_DISCOVERY_NAME,
                'parameters' => self::DEFAULT_TASK_PARAMETERS,
            ],
        ];
    }

    public function testCreateUsesExistingMatchingTask()
    {
        $taskType = TaskTypeFactory::create(TaskTypeService::HTML_VALIDATION_NAME);
        $taskStartingState = TaskService::TASK_STARTING_STATE;

        $stateService = \Mockery::mock(StateService::class);
        $stateService
            ->shouldReceive('fetch')
            ->with(TaskService::TASK_STARTING_STATE)
            ->andReturn(StateFactory::create($taskStartingState));

        $existingTask = new Task();

        $taskRepository = \Mockery::mock(TaskRepository::class);
        $taskRepository
            ->shouldReceive('findOneBy')
            ->with([
                'state' => $taskStartingState,
                'type' => $taskType,
                'url' => self::DEFAULT_TASK_URL,
            ])
            ->andReturn($existingTask);

        $entityManager = \Mockery::mock(EntityManager::class);
        $entityManager
            ->shouldReceive('getRepository')
            ->with(Task::class)
            ->andReturn($taskRepository);

        $taskService = $this->createTaskService([
            'entityManager' => $entityManager,
            'stateService' => $stateService,
        ]);

        $newTask = $taskService->create(
            self::DEFAULT_TASK_URL,
            $taskType,
            ''
        );

        $this->assertEquals(spl_object_hash($existingTask), spl_object_hash($newTask));
    }

    public function testReportCompletionNoOutput()
    {
        $task = new Task();

        /* @var LoggerInterface|MockInterface $logger */
        $logger = \Mockery::mock(LoggerInterface::class);

        $logger
            ->shouldReceive('info')
            ->once()
            ->with(sprintf(
                'TaskService::reportCompletion: Initialising [%d]',
                $task->getId()
            ));

        $logger
            ->shouldReceive('info')
            ->once()
            ->with(sprintf(
                'TaskService::reportCompletion: Task state is [%s], we can\'t report back just yet',
                $task->getState()
            ));

        $taskService = $this->createTaskService([
            'logger' => $logger,
        ]);

        $taskService->reportCompletion($task);
    }

    /**
     * @param array $services
     *
     * @return TaskService
     */
    private function createTaskService($services = [])
    {
        if (!isset($services['entityManager'])) {
            $services['entityManager'] = \Mockery::mock(EntityManager::class);
        }

        if (!isset($services['logger'])) {
            $services['logger'] = \Mockery::mock(LoggerInterface::class);
        }

        if (!isset($services['stateService'])) {
            $services['stateService'] = \Mockery::mock(StateService::class);
        }

        if (!isset($services['urlService'])) {
            $services['urlService'] = \Mockery::mock(UrlService::class);
        }

        if (!isset($services['coreApplicationRouter'])) {
            $services['coreApplicationRouter'] = \Mockery::mock(CoreApplicationRouter::class);
        }

        if (!isset($services['workerService'])) {
            $services['workerService'] = \Mockery::mock(WorkerService::class);
        }

        if (!isset($services['httpClientService'])) {
            $services['httpClientService'] = \Mockery::mock(HttpClientService::class);
        }

        return new TaskService(
            $services['entityManager'],
            $services['logger'],
            $services['stateService'],
            $services['urlService'],
            $services['coreApplicationRouter'],
            $services['workerService'],
            $services['httpClientService']
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
                ->andReturn(StateFactory::create($stateName));
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
