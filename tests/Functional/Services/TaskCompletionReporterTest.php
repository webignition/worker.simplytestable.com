<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services;

use App\Entity\Task\Output;
use App\Event\TaskEvent;
use App\Event\TaskReportCompletionFailureEvent;
use App\Event\TaskReportCompletionSuccessEvent;
use App\Model\Task\Type;
use App\Services\CoreApplicationHttpClient;
use App\Services\TaskCompletionReporter;
use App\Tests\Services\ObjectReflector;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;
use App\Entity\Task\Task;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Services\HttpMockHandler;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use webignition\InternetMediaType\InternetMediaType;

class TaskCompletionReporterTest extends AbstractBaseTestCase
{
    /**
     * @var TaskCompletionReporter
     */
    private $taskCompletionReporter;

    /**
     * @var HttpMockHandler
     */
    private $httpMockHandler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskCompletionReporter = self::$container->get(TaskCompletionReporter::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
    }

    /**
     * @dataProvider reportCompletionSuccessDataProvider
     */
    public function testReportCompletionSuccess(
        Task $task,
        ResponseInterface $responseFixture,
        array $expectedCreatePostRequestRouteParameters,
        array $expectedCreatePostRequestPostData
    ) {
        $coreApplicationHttpClient = \Mockery::mock(CoreApplicationHttpClient::class);
        $eventDispatcher = \Mockery::mock(EventDispatcherInterface::class);

        ObjectReflector::setProperty(
            $this->taskCompletionReporter,
            TaskCompletionReporter::class,
            'coreApplicationHttpClient',
            $coreApplicationHttpClient
        );

        ObjectReflector::setProperty(
            $this->taskCompletionReporter,
            TaskCompletionReporter::class,
            'eventDispatcher',
            $eventDispatcher
        );

        $reportCompletionRequest = \Mockery::mock(RequestInterface::class);

        $coreApplicationHttpClient
            ->shouldReceive('createPostRequest')
            ->once()
            ->withArgs(function (
                string $routeName,
                array $routeParameters,
                array $postData
            ) use (
                $expectedCreatePostRequestRouteParameters,
                $expectedCreatePostRequestPostData
            ) {
                $this->assertSame('task_complete', $routeName);
                $this->assertSame($expectedCreatePostRequestRouteParameters, $routeParameters);
                $this->assertSame($expectedCreatePostRequestPostData, $postData);

                return true;
            })
            ->andReturn($reportCompletionRequest);

        $coreApplicationHttpClient
            ->shouldReceive('send')
            ->once()
            ->withArgs(function (RequestInterface $request) use ($reportCompletionRequest) {
                $this->assertSame($reportCompletionRequest, $request);

                return true;
            })
            ->andReturn($responseFixture);

        $eventDispatcher
            ->shouldReceive('dispatch')
            ->once()
            ->withArgs(function (string $eventName, TaskReportCompletionSuccessEvent $event) use ($task) {
                $this->assertSame(TaskEvent::TYPE_REPORTED_COMPLETION, $eventName);
                $this->assertTrue($event->isSucceeded());
                $this->assertSame($task, $event->getTask());

                return true;
            });

        $returnValue = $this->taskCompletionReporter->reportCompletion($task);

        $this->assertTrue($returnValue);
    }

    public function reportCompletionSuccessDataProvider(): array
    {
        $dateTime = new \DateTime('2018-12-11 16:08:30');

        return [
            '200 OK' => [
                'task' => $this->createTask(
                    'http://example.com/',
                    Task::STATE_COMPLETED,
                    [
                        'output' => '"output content"',
                        'contentType' => new InternetMediaType('application', 'json'),
                    ],
                    Type::TYPE_HTML_VALIDATION,
                    '',
                    $dateTime
                ),
                'responseHttpFixture' => new Response(200),
                'expectedCreatePostRequestRouteParameters' => [
                    'url' => base64_encode('http://example.com/'),
                    'type' => Type::TYPE_HTML_VALIDATION,
                    'parameter_hash' => md5(''),
                ],
                'expectedCreatePostRequestPostData' => [
                    'end_date_time' => $dateTime->format('c'),
                    'output' => '"output content"',
                    'contentType' => 'application/json',
                    'state' => 'task-' . Task::STATE_COMPLETED,
                    'errorCount' => 0,
                    'warningCount' => 0,
                ],
            ],
            '410 Gone' => [
                'task' => $this->createTask(
                    'http://example.com/',
                    Task::STATE_COMPLETED,
                    [
                        'output' => '"output content"',
                        'contentType' => new InternetMediaType('application', 'json'),
                    ],
                    Type::TYPE_HTML_VALIDATION,
                    '',
                    $dateTime
                ),
                'responseHttpFixture' => new Response(410),
                'expectedCreatePostRequestRouteParameters' => [
                    'url' => base64_encode('http://example.com/'),
                    'type' => Type::TYPE_HTML_VALIDATION,
                    'parameter_hash' => md5(''),
                ],
                'expectedCreatePostRequestPostData' => [
                    'end_date_time' => $dateTime->format('c'),
                    'output' => '"output content"',
                    'contentType' => 'application/json',
                    'state' => 'task-' . Task::STATE_COMPLETED,
                    'errorCount' => 0,
                    'warningCount' => 0,
                ],
            ],
        ];
    }

    /**
     * @dataProvider reportCompletionFailureDataProvider
     */
    public function testReportCompletionFailure(
        Task $task,
        array $responseFixtures,
        string $expectedEventFailureType,
        int $expectedEventStatusCode
    ) {
        $this->httpMockHandler->appendFixtures($responseFixtures);

        $eventDispatcher = \Mockery::mock(EventDispatcherInterface::class);

        ObjectReflector::setProperty(
            $this->taskCompletionReporter,
            TaskCompletionReporter::class,
            'eventDispatcher',
            $eventDispatcher
        );

        $eventDispatcher
            ->shouldReceive('dispatch')
            ->once()
            ->withArgs(function (
                string $eventName,
                TaskReportCompletionFailureEvent $event
            ) use (
                $task,
                $expectedEventFailureType,
                $expectedEventStatusCode
            ) {
                $this->assertSame(TaskEvent::TYPE_REPORTED_COMPLETION, $eventName);
                $this->assertFalse($event->isSucceeded());
                $this->assertSame($task, $event->getTask());
                $this->assertSame($expectedEventFailureType, $event->getFailureType());
                $this->assertSame($expectedEventStatusCode, $event->getStatusCode());

                return true;
            });

        $returnValue = $this->taskCompletionReporter->reportCompletion($task);

        $this->assertFalse($returnValue);
    }

    public function reportCompletionFailureDataProvider(): array
    {
        $dateTime = new \DateTime('2018-12-11 16:08:30');

        return [
            '404 Not Found' => [
                'task' => $this->createTask(
                    'http://example.com/',
                    Task::STATE_COMPLETED,
                    [
                        'output' => '"output content"',
                        'contentType' => new InternetMediaType('application', 'json'),
                    ],
                    Type::TYPE_HTML_VALIDATION,
                    '',
                    $dateTime
                ),
                'responseHttpFixtures' => [
                    new Response(404),
                ],
                'expectedEventFailureType' => TaskReportCompletionFailureEvent::FAILURE_TYPE_HTTP,
                'expectedEventStatusCode' => 404,
            ],
            '500 Internal Server Error' => [
                'task' => $this->createTask(
                    'http://example.com/',
                    Task::STATE_COMPLETED,
                    [
                        'output' => '"output content"',
                        'contentType' => new InternetMediaType('application', 'json'),
                    ],
                    Type::TYPE_HTML_VALIDATION,
                    '',
                    $dateTime
                ),
                'responseHttpFixtures' => array_fill(0, 6, new Response(500)),
                'expectedEventFailureType' => TaskReportCompletionFailureEvent::FAILURE_TYPE_HTTP,
                'expectedEventStatusCode' => 500,
            ],
            'cURL Operation Timed Out' => [
                'task' => $this->createTask(
                    'http://example.com/',
                    Task::STATE_COMPLETED,
                    [
                        'output' => '"output content"',
                        'contentType' => new InternetMediaType('application', 'json'),
                    ],
                    Type::TYPE_HTML_VALIDATION,
                    '',
                    $dateTime
                ),
                'responseHttpFixtures' => array_fill(
                    0,
                    6,
                    ConnectExceptionFactory::create('CURL/28 Operation timed out.')
                ),
                'expectedEventFailureType' => TaskReportCompletionFailureEvent::FAILURE_TYPE_CURL,
                'expectedEventStatusCode' => 28,
            ],
            'Unknown' => [
                'task' => $this->createTask(
                    'http://example.com/',
                    Task::STATE_COMPLETED,
                    [
                        'output' => '"output content"',
                        'contentType' => new InternetMediaType('application', 'json'),
                    ],
                    Type::TYPE_HTML_VALIDATION,
                    '',
                    $dateTime
                ),
                'responseHttpFixtures' => array_fill(
                    0,
                    6,
                    new ConnectException('Unknown', \Mockery::mock(RequestInterface::class))
                ),
                'expectedEventFailureType' => TaskReportCompletionFailureEvent::FAILURE_TYPE_UNKNOWN,
                'expectedEventStatusCode' => 0,
            ],
        ];
    }

    private function createTask(
        string $url,
        string $state,
        array $outputValues,
        string $type,
        string $parameters,
        \DateTime $endDateTime
    ): Task {
        $task = Task::create(new Type($type, true, null), $url, $parameters);
        $task->setState($state);

        $task->setOutput(Output::create($outputValues['output'], $outputValues['contentType']));
        $task->setEndDateTime($endDateTime);

        return $task;
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
