<?php

namespace Tests\WorkerBundle\Functional\Services;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;
use Tests\WorkerBundle\Factory\ConnectExceptionFactory;
use Tests\WorkerBundle\Factory\HtmlValidatorFixtureFactory;
use Tests\WorkerBundle\Factory\TestTaskFactory;
use Tests\WorkerBundle\Utility\File;

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
     * @dataProvider createDataProvider
     *
     * @param $url
     * @param $taskTypeName
     * @param $parameters
     */
    public function testCreate($url, $taskTypeName, $parameters)
    {
        $taskType = $this->taskTypeService->fetch($taskTypeName);
        $task = $this->taskService->create($url, $taskType, $parameters);
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
        $this->removeAllTasks();
        $existingTask = $this->taskService->create(
            self::DEFAULT_TASK_URL,
            $this->taskTypeService->getHtmlValidationTaskType(),
            ''
        );
        $this->taskService->persistAndFlush($existingTask);
        $newTask = $this->taskService->create(
            self::DEFAULT_TASK_URL,
            $this->taskTypeService->getHtmlValidationTaskType(),
            ''
        );
        $this->assertEquals($existingTask->getId(), $newTask->getId());
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

        $task = $this->getTestTaskFactory()->create($taskValues);

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
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([]),
                'httpFixtures' => [
                    "HTTP/1.1 200 OK\nContent-type:text/html",
                    "HTTP/1.1 200 OK\nContent-type:text/html\n\n<!doctype html><html><head></head><body></body>"
                ],
                'expectedFinishedStateName' => TaskService::TASK_COMPLETED_STATE,
            ],
            'skipped' => [
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([]),
                'httpFixtures' => [
                    "HTTP/1.1 200 OK\nContent-type:application/pdf"
                ],
                'expectedFinishedStateName' => TaskService::TASK_SKIPPED_STATE,
            ],
            'failed, no retry available' => [
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([]),
                'httpFixtures' => [
                    "HTTP/1.1 404",
                    "HTTP/1.1 404",
                    "HTTP/1.1 404",
                    "HTTP/1.1 404",
                    "HTTP/1.1 404",
                    "HTTP/1.1 404",
                ],
                'expectedFinishedStateName' => TaskService::TASK_FAILED_NO_RETRY_AVAILABLE_STATE,
            ],
        ];
    }

    public function testGetById()
    {
        $task = $this->getTestTaskFactory()->create(TestTaskFactory::createTaskValuesFromDefaults());
        $id = $task->getId();

        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $entityManager->detach($task);

        $this->assertEquals($id, $this->taskService->getById($id)->getId());
    }

    public function testReportCompletionNoOutput()
    {
        $task = $this->getTestTaskFactory()->create(TestTaskFactory::createTaskValuesFromDefaults([]));
        $this->taskService->reportCompletion($task);

        $lastLogLine = File::tail($this->container->get('kernel')->getLogDir() . '/test.log', 1);
        $this->assertRegExp(
            sprintf(
                '/%s/',
                preg_quote(
                    "TaskService::reportCompletion: Task state is [task-queued], we can't report back just yet"
                )
            ),
            $lastLogLine
        );
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
            "HTTP/1.1 200 OK\nContent-type:text/html",
            "HTTP/1.1 200 OK\nContent-type:text/html\n\n<!doctype html><html>",
            $responseFixture,
        ]);
        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->getTestTaskFactory()->create(TestTaskFactory::createTaskValuesFromDefaults([]));
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
            "HTTP/1.1 200 OK\nContent-type:text/html",
            "HTTP/1.1 200 OK\nContent-type:text/html\n\n<!doctype html><html>",
            $responseFixture,
        ]);
        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->getTestTaskFactory()->create(TestTaskFactory::createTaskValuesFromDefaults([]));

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

        $this->getTestTaskFactory()->create(TestTaskFactory::createTaskValuesFromDefaults([
            'state' => TaskService::TASK_STARTING_STATE,
            'type' => TaskTypeService::HTML_VALIDATION_NAME,
        ]));

        $this->getTestTaskFactory()->create(TestTaskFactory::createTaskValuesFromDefaults([
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
