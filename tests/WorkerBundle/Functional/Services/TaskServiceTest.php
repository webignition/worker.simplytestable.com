<?php

namespace Tests\WorkerBundle\Functional\Services;

use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;
use Tests\WorkerBundle\Factory\ConnectExceptionFactory;
use Tests\WorkerBundle\Factory\HtmlValidatorFixtureFactory;
use Tests\WorkerBundle\Factory\TaskFactory;

class TaskServiceTest extends BaseSimplyTestableTestCase
{
    const DEFAULT_TASK_URL = 'http://example.com/';
    const DEFAULT_TASK_PARAMETERS = '';
    const DEFAULT_TASK_TYPE = TaskTypeService::HTML_VALIDATION_NAME;
    const DEFAULT_TASK_STATE = TaskService::TASK_STARTING_STATE;

    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * @var TaskTypeService
     */
    private $taskTypeService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskService = $this->container->get(TaskService::class);
        $this->taskTypeService = $this->container->get(TaskTypeService::class);
    }

    /**
     * @dataProvider getStateDataProvider
     *
     * @param string $method
     * @param string $expectedStateName
     */
    public function testGetState($method, $expectedStateName)
    {
        $state = call_user_func(array($this->taskService, $method));
        $this->assertEquals($expectedStateName, $state->getName());
    }

    /**
     * @return array
     */
    public function getStateDataProvider()
    {
        return [
            'queued' => [
                'method' => 'getQueuedState',
                'expectedStateName' => TaskService::TASK_STARTING_STATE,
            ],
            'in progress' => [
                'method' => 'getInProgressState',
                'expectedStateName' => TaskService::TASK_IN_PROGRESS_STATE,
            ],
            'completed' => [
                'method' => 'getCompletedState',
                'expectedStateName' => TaskService::TASK_COMPLETED_STATE,
            ],
            'cancelled' => [
                'method' => 'getCancelledState',
                'expectedStateName' => TaskService::TASK_CANCELLED_STATE,
            ],
            'failed no retry available' => [
                'method' => 'getFailedNoRetryAvailableState',
                'expectedStateName' => TaskService::TASK_FAILED_NO_RETRY_AVAILABLE_STATE,
            ],
            'failed retry available' => [
                'method' => 'getFailedRetryAvailableState',
                'expectedStateName' => TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
            ],
            'failed retry limit reached' => [
                'method' => 'getFailedRetryLimitReachedState',
                'expectedStateName' => TaskService::TASK_FAILED_RETRY_LIMIT_REACHED_STATE,
            ],
            'skipped' => [
                'method' => 'getSkippedState',
                'expectedStateName' => TaskService::TASK_SKIPPED_STATE,
            ],
        ];
    }

    /**
     * @dataProvider performDataProvider
     *
     * @param array $taskValues
     * @param array $httpFixtures
     * @param string $expectedFinishedStateName
     */
    public function testPerform($taskValues, $httpFixtures, $expectedFinishedStateName)
    {
        $this->setHttpFixtures($httpFixtures);
        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->getTaskFactory()->create($taskValues);

        $this->taskService->perform($task);

        $this->assertEquals($expectedFinishedStateName, $task->getState());
    }

    /**
     * @return array
     */
    public function performDataProvider()
    {
        return [
            'default' => [
                'taskValues' => TaskFactory::createTaskValuesFromDefaults([]),
                'httpFixtures' => [
                    "HTTP/1.1 200 OK\nContent-type:text/html\n\n<!doctype html><html><head></head><body></body>"
                ],
                'expectedFinishedStateName' => TaskService::TASK_COMPLETED_STATE,
            ],
            'skipped' => [
                'taskValues' => TaskFactory::createTaskValuesFromDefaults([]),
                'httpFixtures' => [
                    "HTTP/1.1 200 OK\nContent-type:application/pdf"
                ],
                'expectedFinishedStateName' => TaskService::TASK_SKIPPED_STATE,
            ],
            'failed, no retry available' => [
                'taskValues' => TaskFactory::createTaskValuesFromDefaults([]),
                'httpFixtures' => [
                    "HTTP/1.1 404",
                    "HTTP/1.1 404",
                ],
                'expectedFinishedStateName' => TaskService::TASK_FAILED_NO_RETRY_AVAILABLE_STATE,
            ],
        ];
    }

    public function testGetById()
    {
        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults());
        $id = $task->getId();

        $this->getEntityManager()->detach($task);

        $this->assertEquals($id, $this->taskService->getById($id)->getId());
    }

    /**
     * @dataProvider reportCompletionFailureDataProvider
     *
     * @param $responseFixture
     * @param $expectedReturnValue
     */
    public function testReportCompletionFailure($responseFixture, $expectedReturnValue)
    {
        $this->setHttpFixtures([
            "HTTP/1.1 200 OK\nContent-type:text/html\n\n<!doctype html><html>",
            $responseFixture,
        ]);
        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([]));
        $this->taskService->perform($task);
        $initialTaskState = (string)$task->getState();

        $this->assertEquals($expectedReturnValue, $this->taskService->reportCompletion($task));
        $this->assertEquals($initialTaskState, (string)$task->getState());
        $this->assertInternalType('int', $task->getId());
        $this->assertInternalType('int', $task->getOutput()->getId());
        $this->assertInternalType('int', $task->getTimePeriod()->getId());
    }

    /**
     * @return array
     */
    public function reportCompletionFailureDataProvider()
    {
        return [
            'http 404' => [
                'responseFixture' => "HTTP/1.1 404",
                'expectedReturnValue' => 404,
            ],
            'http 500' => [
                'responseFixture' => "HTTP/1.1 500",
                'expectedReturnValue' => 500,
            ],
            'curl 28' => [
                'responseFixture' => ConnectExceptionFactory::create('CURL/28 Operation timed out.'),
                'expectedReturnValue' => 28,
            ],
        ];
    }

    /**
     * @dataProvider reportCompletionDataProvider
     *
     * @param string $responseFixture
     */
    public function testReportCompletion($responseFixture)
    {
        $this->setHttpFixtures([
            "HTTP/1.1 200 OK\nContent-type:text/html\n\n<!doctype html><html>",
            $responseFixture,
        ]);
        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([]));

        $this->assertEquals(0, $this->taskService->perform($task));
        $this->assertInternalType('int', $task->getId());
        $this->assertInternalType('int', $task->getOutput()->getId());
        $this->assertInternalType('int', $task->getTimePeriod()->getId());

        $this->assertTrue($this->taskService->reportCompletion($task));
        $this->assertEquals(TaskService::TASK_COMPLETED_STATE, (string)$task->getState());

        $this->assertNull($task->getId());
        $this->assertNull($task->getOutput()->getId());
        $this->assertNull($task->getTimePeriod()->getId());
    }

    /**
     * @return array
     */
    public function reportCompletionDataProvider()
    {
        return [
            '200 OK' => [
                'responseHttpFixture' => 'HTTP/1.1 200 OK',
            ],
            '410 Gone' => [
                'responseHttpFixture' => 'HTTP/1.1 410 Gone',
            ],
        ];
    }

    public function testGetIncompleteCount()
    {
        $this->removeAllTasks();

        $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([
            'state' => TaskService::TASK_STARTING_STATE,
            'type' => TaskTypeService::HTML_VALIDATION_NAME,
        ]));

        $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([
            'state' => TaskService::TASK_IN_PROGRESS_STATE,
            'type' => TaskTypeService::CSS_VALIDATION_NAME,
        ]));

        $this->assertEquals(2, $this->taskService->getInCompleteCount());
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
