<?php

namespace SimplyTestable\WorkerBundle\Tests\Unit\Services;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SimplyTestable\WorkerBundle\Entity\State;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Services\CoreApplicationRouter;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\StateService;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use SimplyTestable\WorkerBundle\Services\UrlService;
use SimplyTestable\WorkerBundle\Services\WorkerService;

class TaskServiceTest extends \PHPUnit_Framework_TestCase
{
    const DEFAULT_TASK_URL = 'http://example.com/';
    const DEFAULT_TASK_PARAMETERS = '';
    const DEFAULT_TASK_TYPE = TaskTypeService::HTML_VALIDATION_NAME;
    const DEFAULT_TASK_STATE = TaskService::TASK_STARTING_STATE;

    /**
     * @dataProvider cancelDataProvider
     *
     * @param string $stateName
     * @param string $expectedEndState
     */
    public function testCancel($stateName, $expectedEndState)
    {
        $state = new State();
        $state->setName($stateName);

        $task = new Task();
        $task->setState($state);

        $taskService = $this->createTaskService();

        $taskService->cancel($task);
        $this->assertEquals($expectedEndState, $task->getState());
    }

    /**
     * @return array
     */
    public function cancelDataProvider()
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
     * @dataProvider performTaskInIncorrectStateDataProvider
     *
     * @param string $stateName
     */
    public function testPerformTaskInIncorrectState($stateName)
    {
        $state = new State();
        $state->setName($stateName);

        $task = new Task();
        $task->setState($state);

        $logger = \Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('info');

        $taskService = $this->createTaskService([
            'logger' => $logger,
        ]);

        $this->assertEquals(1, $taskService->perform($task));
    }

    /**
     * @return array
     */
    public function performTaskInIncorrectStateDataProvider()
    {
        return [
            'in-progress' => [
                'stateName' => TaskService::TASK_IN_PROGRESS_STATE,
            ],
            'completed' => [
                'stateName' => TaskService::TASK_COMPLETED_STATE,
            ],
            'cancelled' => [
                'stateName' => TaskService::TASK_CANCELLED_STATE,
            ],
            'failed-no-retry-available' => [
                'stateName' => TaskService::TASK_FAILED_NO_RETRY_AVAILABLE_STATE,
            ],
            'failed-retry-available' => [
                'stateName' => TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
            ],
            'failed-retry-limit-reached' => [
                'stateName' => TaskService::TASK_FAILED_RETRY_LIMIT_REACHED_STATE,
            ],
            'skipped' => [
                'stateName' => TaskService::TASK_SKIPPED_STATE,
            ],
        ];
    }

    /**
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
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
