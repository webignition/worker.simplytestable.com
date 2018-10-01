<?php

namespace App\Tests\Functional\Services;

use App\Model\Task\Type;
use App\Model\Task\TypeInterface;
use App\Services\TaskPerformanceService;
use App\Services\TaskTypeService;
use App\Tests\TestServices\TaskFactory;
use Doctrine\ORM\OptimisticLockException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use App\Entity\Task\Task;
use App\Services\HttpRetryMiddleware;
use App\Services\TaskService;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Factory\HtmlValidatorFixtureFactory;
use App\Tests\Services\HttpMockHandler;
use App\Tests\Utility\File;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

/**
 * @group TaskService
 */
class TaskServiceTest extends AbstractBaseTestCase
{
    const DEFAULT_TASK_URL = 'http://example.com/';
    const DEFAULT_TASK_PARAMETERS = '';
    const DEFAULT_TASK_TYPE = TypeInterface::TYPE_HTML_VALIDATION;
    const DEFAULT_TASK_STATE = Task::STATE_QUEUED;

    /**
     * @var TaskService
     */
    private $taskService;

    /**
     * @var TaskFactory
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
        $this->testTaskFactory = self::$container->get(TaskFactory::class);
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
    public function testCreate(string $url, string $taskTypeName, string $parameters)
    {
        $taskTypeService = self::$container->get(TaskTypeService::class);

        $task = $this->taskService->create($url, $taskTypeService->get($taskTypeName), $parameters);
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
                'taskTypeName' => TypeInterface::TYPE_HTML_VALIDATION,
                'parameters' => self::DEFAULT_TASK_PARAMETERS,
            ],
            'css validation default' => [
                'url' => self::DEFAULT_TASK_URL,
                'taskTypeName' => TypeInterface::TYPE_CSS_VALIDATION,
                'parameters' => self::DEFAULT_TASK_PARAMETERS,
            ],
            'link integrity default' => [
                'url' => self::DEFAULT_TASK_URL,
                'taskTypeName' => TypeInterface::TYPE_LINK_INTEGRITY,
                'parameters' => self::DEFAULT_TASK_PARAMETERS,
            ],
            'url discovery default' => [
                'url' => self::DEFAULT_TASK_URL,
                'taskTypeName' => TypeInterface::TYPE_URL_DISCOVERY,
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
        $taskTypeService = self::$container->get(TaskTypeService::class);

        $existingTask = $this->taskService->create(
            self::DEFAULT_TASK_URL,
            $taskTypeService->get(TypeInterface::TYPE_HTML_VALIDATION),
            ''
        );

        $entityManager->persist($existingTask);
        $entityManager->flush();

        $newTask = $this->taskService->create(
            self::DEFAULT_TASK_URL,
            $taskTypeService->get(TypeInterface::TYPE_HTML_VALIDATION),
            ''
        );
        $this->assertEquals($existingTask->getId(), $newTask->getId());
    }

    public function testGetById()
    {
        $task = $this->testTaskFactory->create(TaskFactory::createTaskValuesFromDefaults());
        $id = $task->getId();

        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $entityManager->detach($task);

        $retrievedTask = $this->taskService->getById($id);

        $this->assertEquals($id, $retrievedTask->getId());
        $this->assertInstanceOf(Type::class, $retrievedTask->getType());
    }

    /**
     * @throws GuzzleException
     */
    public function testReportCompletionNoOutput()
    {
        $task = $this->testTaskFactory->create(TaskFactory::createTaskValuesFromDefaults([]));
        $this->taskService->reportCompletion($task);

        $lastLogLine = File::tail(self::$container->get('kernel')->getLogDir() . '/test.log', 1);
        $this->assertRegExp(
            sprintf(
                '/%s/',
                preg_quote(
                    "TaskService::reportCompletion: Task state is [queued], we can't report back just yet"
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
        $taskPerformanceService = self::$container->get(TaskPerformanceService::class);

        $this->httpMockHandler->appendFixtures(array_merge([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], '<!doctype html><html>'),
        ], $responseFixtures));

        $this->httpRetryMiddleware->disable();

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->testTaskFactory->create(TaskFactory::createTaskValuesFromDefaults([]));
        $taskPerformanceService->perform($task);
        $initialTaskState = $task->getState();

        $this->assertEquals($expectedReturnValue, $this->taskService->reportCompletion($task));
        $this->assertEquals($initialTaskState, $task->getState());
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
        $taskPerformanceService = self::$container->get(TaskPerformanceService::class);

        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], '<!doctype html><html>'),
            $responseFixture,
        ]);
        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->testTaskFactory->create(TaskFactory::createTaskValuesFromDefaults([]));

        $taskPerformanceService->perform($task);
        $this->assertInternalType('int', $task->getId());
        $this->assertInternalType('int', $task->getOutput()->getId());
        $this->assertInternalType('int', $task->getTimePeriod()->getId());

        $this->assertTrue($this->taskService->reportCompletion($task));
        $this->assertEquals(Task::STATE_COMPLETED, $task->getState());

        $this->assertNull($task->getId());
        $this->assertNull($task->getOutput()->getId());
        $this->assertNull($task->getTimePeriod()->getId());

        $lastRequest = $this->httpHistoryContainer->getLastRequest();

        $this->assertEquals('application/x-www-form-urlencoded', $lastRequest->getHeaderLine('content-type'));
        $this->assertEquals(
            '/task/aHR0cDovL2V4YW1wbGUuY29tLw%3D%3D/html%20validation/d41d8cd98f00b204e9800998ecf8427e/complete/',
            $lastRequest->getUri()->getPath()
        );

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
                'state' => 'task-' . Task::STATE_COMPLETED,
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
        $this->testTaskFactory->create(TaskFactory::createTaskValuesFromDefaults([
            'state' => Task::STATE_QUEUED,
            'type' => TypeInterface::TYPE_HTML_VALIDATION,
        ]));

        $this->testTaskFactory->create(TaskFactory::createTaskValuesFromDefaults([
            'state' => Task::STATE_IN_PROGRESS,
            'type' => TypeInterface::TYPE_CSS_VALIDATION,
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
