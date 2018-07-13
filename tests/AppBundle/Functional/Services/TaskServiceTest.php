<?php

namespace Tests\AppBundle\Functional\Services;

use Doctrine\ORM\OptimisticLockException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use AppBundle\Entity\Task\Task;
use AppBundle\Services\HttpRetryMiddleware;
use AppBundle\Services\TaskService;
use AppBundle\Services\TaskTypeService;
use Tests\AppBundle\Functional\AbstractBaseTestCase;
use Tests\AppBundle\Factory\ConnectExceptionFactory;
use Tests\AppBundle\Factory\HtmlValidatorFixtureFactory;
use Tests\AppBundle\Factory\TestTaskFactory;
use Tests\AppBundle\Services\HttpMockHandler;
use Tests\AppBundle\Utility\File;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

/**
 * @group TaskService
 */
class TaskServiceTest extends AbstractBaseTestCase
{
    const DEFAULT_TASK_URL = 'http://example.com/';
    const DEFAULT_TASK_PARAMETERS = '';
    const DEFAULT_TASK_TYPE = TaskTypeService::HTML_VALIDATION_NAME;
    const DEFAULT_TASK_STATE = Task::STATE_QUEUED;

    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * @var TaskTypeService
     */
    private $taskTypeService;

    /**
     * @var TestTaskFactory
     */
    private $testTaskFactory;

    /**
     * @var HttpMockHandler
     */
    private $httpMockHandler;

    /**
     * @var HttpHistoryContainer
     */
    private $httpHistoryContainer;

    /**
     * @var HttpRetryMiddleware
     */
    private $httpRetryMiddleware;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskService = self::$container->get(TaskService::class);
        $this->taskTypeService = self::$container->get(TaskTypeService::class);
        $this->testTaskFactory = new TestTaskFactory(self::$container);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
        $this->httpHistoryContainer = self::$container->get(HttpHistoryContainer::class);
        $this->httpRetryMiddleware = self::$container->get(HttpRetryMiddleware::class);
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
        $this->assertEquals(Task::STATE_QUEUED, $task->getState());
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

    /**
     * @throws OptimisticLockException
     * @throws \Doctrine\ORM\ORMException
     */
    public function testCreateUsesExistingMatchingTask()
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

        $existingTask = $this->taskService->create(
            self::DEFAULT_TASK_URL,
            $this->taskTypeService->getHtmlValidationTaskType(),
            ''
        );

        $entityManager->persist($existingTask);
        $entityManager->flush();

        $newTask = $this->taskService->create(
            self::DEFAULT_TASK_URL,
            $this->taskTypeService->getHtmlValidationTaskType(),
            ''
        );
        $this->assertEquals($existingTask->getId(), $newTask->getId());
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
        $this->httpMockHandler->appendFixtures($httpFixtures);
        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->testTaskFactory->create($taskValues);

        $this->taskService->perform($task);

        $this->assertEquals($expectedFinishedStateName, $task->getState());
    }

    /**
     * @return array
     */
    public function performDataProvider()
    {
        $notFoundResponse = new Response(404);

        return [
            'default' => [
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([]),
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        '<!doctype html><html><head></head><body></body>'
                    ),
                ],
                'expectedFinishedStateName' => Task::STATE_COMPLETED,
            ],
            'skipped' => [
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([]),
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'application/pdf']),
                ],
                'expectedFinishedStateName' => Task::STATE_SKIPPED,
            ],
            'failed, no retry available' => [
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([]),
                'httpFixtures' => [
                    $notFoundResponse,
                    $notFoundResponse,
                ],
                'expectedFinishedStateName' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
            ],
        ];
    }

    public function testGetById()
    {
        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults());
        $id = $task->getId();

        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $entityManager->detach($task);

        $this->assertEquals($id, $this->taskService->getById($id)->getId());
    }

    /**
     * @throws GuzzleException
     */
    public function testReportCompletionNoOutput()
    {
        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([]));
        $this->taskService->reportCompletion($task);

        $lastLogLine = File::tail(self::$container->get('kernel')->getLogDir() . '/test.log', 1);
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
     * @param array $responseFixtures
     * @param int $expectedReturnValue
     *
     * @throws GuzzleException
     */
    public function testReportCompletionFailure(array $responseFixtures, $expectedReturnValue)
    {
        $this->httpMockHandler->appendFixtures(array_merge([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], '<!doctype html><html>'),
        ], $responseFixtures));

        $this->httpRetryMiddleware->disable();

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([]));
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
                'responseFixtures' => [
                    new Response(404),
                ],
                'expectedReturnValue' => 404,
            ],
            'http 500' => [
                'responseFixtures' => [
                    new Response(500),
                ],
                'expectedReturnValue' => 500,
            ],
            'curl 28' => [
                'responseFixtures' => [
                    ConnectExceptionFactory::create('CURL/28 Operation timed out.'),
                ],
                'expectedReturnValue' => 28,
            ],
        ];
    }

    /**
     * @dataProvider reportCompletionSuccessDataProvider
     *
     * @param string $responseFixture
     *
     * @throws GuzzleException
     */
    public function testReportCompletionSuccess($responseFixture)
    {
        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], '<!doctype html><html>'),
            $responseFixture,
        ]);
        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([]));

        $this->taskService->perform($task);
        $this->assertInternalType('int', $task->getId());
        $this->assertInternalType('int', $task->getOutput()->getId());
        $this->assertInternalType('int', $task->getTimePeriod()->getId());

        $this->assertTrue($this->taskService->reportCompletion($task));
        $this->assertEquals(Task::STATE_COMPLETED, (string)$task->getState());

        $this->assertNull($task->getId());
        $this->assertNull($task->getOutput()->getId());
        $this->assertNull($task->getTimePeriod()->getId());

        $lastRequest = $this->httpHistoryContainer->getLastRequest();

        $this->assertEquals('application/x-www-form-urlencoded', $lastRequest->getHeaderLine('content-type'));

        $postedData = [];
        parse_str(urldecode($lastRequest->getBody()->getContents()), $postedData);

        $this->assertRegExp(
            '/^[\d]{4}-[\d]{2}-[\d]{2}T[\d]{2}:[\d]{2}:[\d]{2} [\d]{2}:[\d]{2}/',
            $postedData['end_date_time']
        );

        $this->assertArraySubset(
            [
                'output' => '{"messages":[]}',
                'contentType' => 'application/json',
                'state' => 'task-completed',
                'errorCount' => '0',
                'warningCount' => '0',
            ],
            $postedData
        );
    }

    /**
     * @return array
     */
    public function reportCompletionSuccessDataProvider()
    {
        return [
            '200 OK' => [
                'responseHttpFixture' => new Response(200),
            ],
            '410 Gone' => [
                'responseHttpFixture' => new Response(410),
            ],
        ];
    }

    public function testGetIncompleteCount()
    {
        $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'state' => Task::STATE_QUEUED,
            'type' => TaskTypeService::HTML_VALIDATION_NAME,
        ]));

        $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'state' => Task::STATE_IN_PROGRESS,
            'type' => TaskTypeService::CSS_VALIDATION_NAME,
        ]));

        $this->assertEquals(2, $this->taskService->getInCompleteCount());
    }

    protected function assertPostConditions()
    {
        parent::assertPostConditions();

        $this->assertEquals(0, $this->httpMockHandler->count());
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