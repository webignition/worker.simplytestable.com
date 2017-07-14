<?php

namespace Tests\WorkerBundle\Unit\Services;

use Doctrine\ORM\EntityManager;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Services\CoreApplicationRouter;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\StateService;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use SimplyTestable\WorkerBundle\Services\UrlService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Tests\WorkerBundle\Factory\MockEntityFactory;

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
        $task->setState(MockEntityFactory::createState($stateName));

        $taskService = $this->createTaskService([
            'stateService' => $this->createStateService([
                TaskService::TASK_CANCELLED_STATE,
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
    public function testCancel($stateName, $expectedEndState)
    {
        $task = new Task();
        $task->setState(MockEntityFactory::createState($stateName));

        $stateService = \Mockery::mock(StateService::class);
        $stateService
            ->shouldReceive('fetch')
            ->with(TaskService::TASK_CANCELLED_STATE)
            ->andReturn(MockEntityFactory::createState(TaskService::TASK_CANCELLED_STATE));

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
        $this->assertEquals($expectedEndState, $task->getState()->getName());
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
