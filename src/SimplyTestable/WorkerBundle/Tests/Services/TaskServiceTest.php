<?php

namespace SimplyTestable\WorkerBundle\Tests\Services;

use SimplyTestable\WorkerBundle\Entity\Task\Output as TaskOutput;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Entity\TimePeriod;
use SimplyTestable\WorkerBundle\Model\TaskDriver\Response as TaskDriverResponse;
use SimplyTestable\WorkerBundle\Services\TaskDriver\HtmlValidationTaskDriver;
use SimplyTestable\WorkerBundle\Services\TaskDriver\TaskDriver;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use SimplyTestable\WorkerBundle\Tests\BaseSimplyTestableTestCase;

class TaskServiceTest extends BaseSimplyTestableTestCase
{
    const DEFAULT_TASK_URL = 'http://example.com/';
    const DEFAULT_TASK_PARAMETERS = '';
    const DEFAULT_TASK_TYPE = TaskTypeService::HTML_VALIDATION_NAME;
    const DEFAULT_TASK_STATE = TaskService::TASK_STARTING_STATE;

    /**
     * @var array
     */
    private $defaultTaskValues = [
        'url' => self::DEFAULT_TASK_URL,
        'type' => self::DEFAULT_TASK_TYPE,
        'parameters' => self::DEFAULT_TASK_PARAMETERS,
        'state' => self::DEFAULT_TASK_STATE,
    ];

    /**
     * @inheritdoc
     */
    protected static function getMockServices()
    {
        return [
            'foo' => HtmlValidationTaskDriver::class,
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
        $task = $this->createTask($taskValues);
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
                'task' => $this->createTaskValuesFromDefaults([
                    'state' => TaskService::TASK_CANCELLED_STATE,
                ]),
                'expectedEndState' => TaskService::TASK_CANCELLED_STATE,
            ],
            'state: completed' => [
                'task' => $this->createTaskValuesFromDefaults([
                    'state' => TaskService::TASK_COMPLETED_STATE,
                ]),
                'expectedEndState' => TaskService::TASK_COMPLETED_STATE,
            ],
            'state: queued' => [
                'task' => $this->createTaskValuesFromDefaults(),
                'expectedEndState' => TaskService::TASK_CANCELLED_STATE,
            ],
            'state: in-progress' => [
                'task' => $this->createTaskValuesFromDefaults([
                    'state' => TaskService::TASK_IN_PROGRESS_STATE,
                ]),
                'expectedEndState' => TaskService::TASK_CANCELLED_STATE,
            ],
        ];
    }

    /**
     * @dataProvider completeDataProvider
     *
     * @param array $taskValues
     * @param array $taskDriverResponseValues
     * @param string $expectedEndState
     */
    public function testComplete(
        array $taskValues,
        array $taskDriverResponseValues,
        $expectedEndState,
        $expectedTaskOutput
    ) {
        $taskDriverResponse = $this->createTaskDriverResponse($taskDriverResponseValues);

        $task = $this->createTask($taskValues);
        $this->startTask($task);
        $this->assertEquals(TaskService::TASK_IN_PROGRESS_STATE, $task->getState());
        $this->assertNull($task->getOutput());

        $this->getTaskService()->complete($task, $taskDriverResponse);
        $this->assertEquals($expectedEndState, $task->getState());
        $this->assertTrue($task->getTimePeriod()->hasEndDateTime());
        $this->assertInstanceOf(TaskOutput::class, $task->getOutput());
        $this->assertEquals($expectedTaskOutput, $task->getOutput()->getOutput());
    }

    /**
     * @return array
     */
    public function completeDataProvider()
    {
        return [
            'completed' => [
                'taskValues' => $this->createTaskValuesFromDefaults(),
                'taskDriverResponse' => [],
                'expectedEndState' => TaskService::TASK_COMPLETED_STATE,
                'expectedTaskOutput' => '',
            ],
            'completed with non-empty output' => [
                'taskValues' => $this->createTaskValuesFromDefaults(),
                'taskDriverResponse' => [
                    'taskOutputValues' => [
                        'output' => 'foo'
                    ],
                ],
                'expectedEndState' => TaskService::TASK_COMPLETED_STATE,
                'expectedTaskOutput' => 'foo',
            ],
            'skipped' => [
                'taskValues' => $this->createTaskValuesFromDefaults(),
                'taskDriverResponse' => [
                    'hasBeenSkipped' => true,
                ],
                'expectedEndState' => TaskService::TASK_SKIPPED_STATE,
                'expectedTaskOutput' => '',
            ],
            'failed, retry limit reached' => [
                'taskValues' => $this->createTaskValuesFromDefaults(),
                'taskDriverResponse' => [
                    'hasSucceeded' => false,
                    'retryLimitReached' => true,
                ],
                'expectedEndState' => TaskService::TASK_FAILED_RETRY_LIMIT_REACHED_STATE,
                'expectedTaskOutput' => '',
            ],
            'failed, retry available' => [
                'taskValues' => $this->createTaskValuesFromDefaults(),
                'taskDriverResponse' => [
                    'hasSucceeded' => false,
                    'retryLimitReached' => false,
                    'retryable' => true,
                ],
                'expectedEndState' => TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                'expectedTaskOutput' => '',
            ],
            'failed, no retry available' => [
                'taskValues' => $this->createTaskValuesFromDefaults(),
                'taskDriverResponse' => [
                    'hasSucceeded' => false,
                    'retryLimitReached' => false,
                    'retryable' => false,
                ],
                'expectedEndState' => TaskService::TASK_FAILED_NO_RETRY_AVAILABLE_STATE,
                'expectedTaskOutput' => '',
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
     * @dataProvider performDataProvider
     *
     * @param $taskValues
     */
    public function testPerform($taskValues)
    {
        $task = $this->createTask($taskValues);

        $this->getTaskService()->perform($task);
    }

    /**
     * @return array
     */
    public function performDataProvider()
    {
        return [
            'foo' => [
                'taskValues' => $this->createTaskValuesFromDefaults([]),
            ],
        ];
    }

    /**
     * @param array $taskValues
     *
     * @return array
     */
    private function createTaskValuesFromDefaults(array $taskValues = [])
    {
        return array_merge($this->defaultTaskValues, $taskValues);
    }

    /**
     * @param array $taskDriverResponseValues
     * @return TaskDriverResponse
     */
    private function createTaskDriverResponse($taskDriverResponseValues)
    {
        $taskOutputValues = isset($taskDriverResponseValues['taskOutputValues'])
            ? $taskDriverResponseValues['taskOutputValues']
            : [
                'output' => '',
            ];

        if (!isset($taskDriverResponseValues['hasSucceeded'])) {
            $taskDriverResponseValues['hasSucceeded'] = true;
        }

        if (!isset($taskDriverResponseValues['hasBeenSkipped'])) {
            $taskDriverResponseValues['hasBeenSkipped'] = false;
        }

        if (!isset($taskDriverResponseValues['retryLimitReached'])) {
            $taskDriverResponseValues['retryLimitReached'] = false;
        }

        if (!isset($taskDriverResponseValues['retryable'])) {
            $taskDriverResponseValues['retryable'] = true;
        }

        $taskOutput = new TaskOutput();
        $taskOutput->setOutput($taskOutputValues['output']);
        $taskOutput->setState(
            $this->getStateService()->fetch(TaskDriver::OUTPUT_STARTING_STATE)
        );

        $taskDriverResponse = new TaskDriverResponse();
        $taskDriverResponse->setTaskOutput($taskOutput);

        if ($taskDriverResponseValues['hasBeenSkipped']) {
            $taskDriverResponse->setHasBeenSkipped();
        }

        if ($taskDriverResponseValues['hasSucceeded']) {
            $taskDriverResponse->setHasSucceeded();
        } else {
            $taskDriverResponse->setHasFailed();
        }

        $taskDriverResponse->setIsRetryLimitReached($taskDriverResponseValues['retryLimitReached']);
        $taskDriverResponse->setIsRetryable($taskDriverResponseValues['retryable']);

        return $taskDriverResponse;
    }

    /**
     * @param Task $task
     *
     * @return Task
     */
    private function startTask(Task $task)
    {
        $timePeriod = new TimePeriod();
        $timePeriod->setStartDateTime(new \DateTime());
        $task->setTimePeriod($timePeriod);
        $task->setState($this->getTaskService()->getInProgressState());

        $this->getTaskService()->persistAndFlush($task);
    }
}
