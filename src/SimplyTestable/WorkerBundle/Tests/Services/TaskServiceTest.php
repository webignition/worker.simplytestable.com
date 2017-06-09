<?php

namespace SimplyTestable\WorkerBundle\Tests\Services;

use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use SimplyTestable\WorkerBundle\Entity\Task\Output as TaskOutput;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Entity\Task\Type\TaskTypeClass;
use SimplyTestable\WorkerBundle\Entity\TimePeriod;
use SimplyTestable\WorkerBundle\Model\TaskDriver\Response as TaskDriverResponse;
use SimplyTestable\WorkerBundle\Services\TaskDriver\HtmlValidationTaskDriver;
use SimplyTestable\WorkerBundle\Services\TaskDriver\TaskDriver;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use SimplyTestable\WorkerBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\WorkerBundle\Tests\Factory\ConnectExceptionFactory;
use SimplyTestable\WorkerBundle\Tests\Factory\HtmlValidatorFixtureFactory;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;

class TaskServiceTest extends BaseSimplyTestableTestCase
{
    const DEFAULT_TASK_URL = 'http://example.com/';
    const DEFAULT_TASK_PARAMETERS = '';
    const DEFAULT_TASK_TYPE = TaskTypeService::HTML_VALIDATION_NAME;
    const DEFAULT_TASK_STATE = TaskService::TASK_STARTING_STATE;

    /**
     * @inheritdoc
     */
    protected static function getServicesToMock()
    {
        return [
            'logger',
        ];
    }

    /**
     * @dataProvider cancelDataProvider
     *
     * @param array $taskValues
     * @param string $expectedEndState
     */
    public function testCancel(array $taskValues, $expectedEndState)
    {
        $task = $this->getTaskFactory()->create($taskValues);
        $this->assertEquals($taskValues['state'], $task->getState());

        $this->getTaskService()->cancel($task);
        $this->assertEquals($expectedEndState, $task->getState());
    }

    /**
     * @return array
     */
    public function cancelDataProvider()
    {
        return [
            'state: cancelled' => [
                'task' => TaskFactory::createTaskValuesFromDefaults([
                    'state' => TaskService::TASK_CANCELLED_STATE,
                ]),
                'expectedEndState' => TaskService::TASK_CANCELLED_STATE,
            ],
            'state: completed' => [
                'task' => TaskFactory::createTaskValuesFromDefaults([
                    'state' => TaskService::TASK_COMPLETED_STATE,
                ]),
                'expectedEndState' => TaskService::TASK_COMPLETED_STATE,
            ],
            'state: queued' => [
                'task' => TaskFactory::createTaskValuesFromDefaults(),
                'expectedEndState' => TaskService::TASK_CANCELLED_STATE,
            ],
            'state: in-progress' => [
                'task' => TaskFactory::createTaskValuesFromDefaults([
                    'state' => TaskService::TASK_IN_PROGRESS_STATE,
                ]),
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
        $taskType = $this->getTaskTypeService()->fetch($taskTypeName);

        $task = $this->getTaskService()->create($url, $taskType, $parameters);

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
        $existingTask = $this->getTaskService()->create(
            self::DEFAULT_TASK_URL,
            $this->getTaskTypeService()->getHtmlValidationTaskType(),
            ''
        );

        $this->getTaskService()->persistAndFlush($existingTask);

        $newTask = $this->getTaskService()->create(
            self::DEFAULT_TASK_URL,
            $this->getTaskTypeService()->getHtmlValidationTaskType(),
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
        $state = call_user_func(array($this->getTaskService(), $method));
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
     * @dataProvider performTaskInIncorrectStateDataProvider
     *
     * @param array $taskValues
     */
    public function testPerformTaskInIncorrectState($taskValues)
    {
        $task = $this->getTaskFactory()->create($taskValues);
        $this->assertEquals(1, $this->getTaskService()->perform($task));
    }

    /**
     * @return array
     */
    public function performTaskInIncorrectStateDataProvider()
    {
        return [
            'in-progress' => [
                'taskValues' => TaskFactory::createTaskValuesFromDefaults([
                    'state' => TaskService::TASK_IN_PROGRESS_STATE,
                ]),
            ],
            'completed' => [
                'taskValues' => TaskFactory::createTaskValuesFromDefaults([
                    'state' => TaskService::TASK_COMPLETED_STATE,
                ]),
            ],
            'cancelled' => [
                'taskValues' => TaskFactory::createTaskValuesFromDefaults([
                    'state' => TaskService::TASK_CANCELLED_STATE,
                ]),
            ],
            'failed-no-retry-available' => [
                'taskValues' => TaskFactory::createTaskValuesFromDefaults([
                    'state' => TaskService::TASK_FAILED_NO_RETRY_AVAILABLE_STATE,
                ]),
            ],
            'failed-retry-available' => [
                'taskValues' => TaskFactory::createTaskValuesFromDefaults([
                    'state' => TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                ]),
            ],
            'failed-retry-limit-reached' => [
                'taskValues' => TaskFactory::createTaskValuesFromDefaults([
                    'state' => TaskService::TASK_FAILED_RETRY_LIMIT_REACHED_STATE,
                ]),
            ],
            'skipped' => [
                'taskValues' => TaskFactory::createTaskValuesFromDefaults([
                    'state' => TaskService::TASK_SKIPPED_STATE,
                ]),
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
        $this->clearMemcacheHttpCache();
        $this->setHttpFixtures($httpFixtures);
        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->getTaskFactory()->create($taskValues);

        $this->getTaskService()->perform($task);

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

        $this->assertEquals($id, $this->getTaskService()->getById($id)->getId());
    }

    public function testReportCompletionNoOutput()
    {
        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([]));

        /* @var LoggerInterface|MockInterface $logger */
        $logger = $this->container->get('logger');

        $logger
            ->shouldReceive('info')
            ->with(sprintf(
                'TaskService::reportCompletion: Initialising [%d]',
                $task->getId()
            ));

        $logger
            ->shouldReceive('info')
            ->with(sprintf(
                'TaskService::reportCompletion: Task state is [%s], we can\'t report back just yet',
                $task->getState()
            ));

        $this->getTaskService()->reportCompletion($task);
    }

    /**
     * @dataProvider reportCompletionFailureDataProvider
     *
     * @param $responseFixture
     * @param $expectedReturnValue
     */
    public function testReportCompletionFailure($responseFixture, $expectedReturnValue)
    {
        $this->clearMemcacheHttpCache();
        $this->setHttpFixtures([
            "HTTP/1.1 200 OK\nContent-type:text/html\n\n<!doctype html><html>",
            $responseFixture,
        ]);
        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([]));
        $this->getTaskService()->perform($task);
        $initialTaskState = (string)$task->getState();

        $this->assertEquals($expectedReturnValue, $this->getTaskService()->reportCompletion($task));
        $this->assertEquals($initialTaskState, (string)$task->getState());
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

    public function testReportCompletion()
    {
        $this->clearMemcacheHttpCache();
        $this->setHttpFixtures([
            "HTTP/1.1 200 OK\nContent-type:text/html\n\n<!doctype html><html>",
            "HTTP/1.1 200 OK",
        ]);
        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([

        ]));
        $this->getTaskService()->perform($task);

        $this->assertTrue($this->getTaskService()->reportCompletion($task));
        $this->assertEquals(TaskService::TASK_COMPLETED_STATE, (string)$task->getState());
    }

    public function testGetIncompleteCount()
    {
        $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([
            'state' => TaskService::TASK_STARTING_STATE,
        ]));
        $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([
            'state' => TaskService::TASK_IN_PROGRESS_STATE,
        ]));

        $this->assertEquals(2, $this->getTaskService()->getInCompleteCount());
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
