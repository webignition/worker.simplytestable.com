<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Functional\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\Task\TypeInterface;
use App\Services\CachedResourceFactory;
use App\Services\CachedResourceManager;
use App\Services\RequestIdentifierFactory;
use App\Services\SourceFactory;
use App\Services\TaskTypePerformer\TaskPerformerInterface;
use App\Tests\Services\HttpMockHandler;
use App\Tests\Services\TestTaskFactory;
use GuzzleHttp\Psr7\Response;
use App\Services\TaskTypePerformer\LinkCheckerConfigurationFactory;
use App\Services\TaskTypePerformer\LinkIntegrityTaskTypePerformer;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Factory\HtmlDocumentFactory;
use webignition\WebResource\WebPage\WebPage;

class LinkIntegrityTaskTypePerformerTest extends AbstractWebPageTaskTypePerformerTest
{
    /**
     * @var LinkIntegrityTaskTypePerformer
     */
    private $taskTypePerformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->taskTypePerformer = self::$container->get(LinkIntegrityTaskTypePerformer::class);
    }

    protected function getTaskTypePerformer(): TaskPerformerInterface
    {
        return self::$container->get(LinkIntegrityTaskTypePerformer::class);
    }

    protected function getTaskTypeString(): string
    {
        return TypeInterface::TYPE_LINK_INTEGRITY;
    }

    /**
     * @dataProvider performSuccessDataProvider
     */
    public function testPerformSuccess(
        callable $taskCreator,
        callable $setUp,
        string $webPageContent,
        string $expectedTaskState,
        int $expectedErrorCount,
        int $expectedWarningCount,
        array $expectedDecodedOutput
    ) {
        /* @var Task $task */
        $task = $taskCreator($webPageContent);
        $setUp($task, $webPageContent);

        $this->setSuccessfulTaskPerformerWebPageRetrieverOnTaskPerformer(
            LinkIntegrityTaskTypePerformer::class,
            $task,
            $webPageContent
        );

        $this->taskTypePerformer->perform($task);

        $this->assertEquals($expectedTaskState, $task->getState());

        $output = $task->getOutput();
        $this->assertInstanceOf(Output::class, $output);
        $this->assertEquals('application/json', $output->getContentType());
        $this->assertEquals($expectedErrorCount, $output->getErrorCount());
        $this->assertEquals($expectedWarningCount, $output->getWarningCount());

        $this->assertEquals(
            $expectedDecodedOutput,
            json_decode($output->getOutput(), true)
        );
    }

    public function performSuccessDataProvider(): array
    {
        return [
            'no links, no sources' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);

                    return $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
                        'type' => $this->getTaskTypeString(),
                    ]));
                },
                'setUp' => function (Task $task, string $webPageContent) {
                    $this->setSuccessfulTaskPerformerWebPageRetrieverOnTaskPerformer(
                        LinkIntegrityTaskTypePerformer::class,
                        $task,
                        $webPageContent
                    );
                },
                'webPageContent' => '<!doctype html><html><head></head><body></body></html>',
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
            'no links, has primary source' => [
                'taskCreator' => function (string $webPageContent): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);
                    $cachedResourceFactory = self::$container->get(CachedResourceFactory::class);
                    $cachedResourceManager = self::$container->get(CachedResourceManager::class);

                    $requestIdentiferFactory = new RequestIdentifierFactory();
                    $sourceFactory = new SourceFactory();

                    $task =  $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults([
                            'type' => $this->getTaskTypeString(),
                        ])
                    );

                    $requestIdentifer = $requestIdentiferFactory->createFromTask($task);

                    /* @var WebPage $webPage */
                    $webPage = WebPage::createFromContent($webPageContent);

                    $cachedResource = $cachedResourceFactory->createForTask(
                        (string) $requestIdentifer,
                        $task,
                        $webPage
                    );

                    $cachedResourceManager->persist($cachedResource);

                    $source = $sourceFactory->fromCachedResource($cachedResource);
                    $task->addSource($source);

                    return $task;
                },
                'setUp' => function () {
                },
                'webPageContent' => '<!doctype html><html><head></head><body></body></html>',
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
            'single 200 OK link, no sources' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);

                    return $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
                        'type' => $this->getTaskTypeString(),
                    ]));
                },
                'setUp' => function (Task $task, string $webPageContent) {
                    $httpMockHandler = self::$container->get(HttpMockHandler::class);
                    $httpMockHandler->appendFixtures([
                        new Response(),
                    ]);

                    $this->setSuccessfulTaskPerformerWebPageRetrieverOnTaskPerformer(
                        LinkIntegrityTaskTypePerformer::class,
                        $task,
                        $webPageContent
                    );
                },
                'webPageContent' => '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>',
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    [
                        'context' => '<a href="/foo"></a>',
                        'state' => 200,
                        'type' => 'http',
                        'url' => 'http://example.com/foo',
                    ],
                ],
            ],
            'single 404 Not Found link, no sources' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);

                    return $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
                        'type' => $this->getTaskTypeString(),
                    ]));
                },
                'setUp' => function (Task $task, string $webPageContent) {
                    $httpMockHandler = self::$container->get(HttpMockHandler::class);
                    $httpMockHandler->appendFixtures([
                        new Response(404),
                        new Response(404),
                    ]);

                    $this->setSuccessfulTaskPerformerWebPageRetrieverOnTaskPerformer(
                        LinkIntegrityTaskTypePerformer::class,
                        $task,
                        $webPageContent
                    );
                },
                'webPageContent' => '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>',
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    [
                        'context' => '<a href="/foo"></a>',
                        'state' => 404,
                        'type' => 'http',
                        'url' => 'http://example.com/foo',
                    ],
                ],
            ],
            'single curl 28 link' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);

                    return $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
                        'type' => $this->getTaskTypeString(),
                    ]));
                },
                'setUp' => function (Task $task, string $webPageContent) {
                    $httpMockHandler = self::$container->get(HttpMockHandler::class);
                    $httpMockHandler->appendFixtures([
                        ConnectExceptionFactory::create('CURL/28 Operation timed out.'),
                    ]);

                    $this->setSuccessfulTaskPerformerWebPageRetrieverOnTaskPerformer(
                        LinkIntegrityTaskTypePerformer::class,
                        $task,
                        $webPageContent
                    );
                },
                'webPageContent' => '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>',
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    [
                        'context' => '<a href="/foo"></a>',
                        'state' => 28,
                        'type' => 'curl',
                        'url' => 'http://example.com/foo',
                    ],
                ],
            ],
            'excluded urls, no sources' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);

                    return $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
                        'type' => $this->getTaskTypeString(),
                        'parameters' => json_encode([
                            LinkCheckerConfigurationFactory::EXCLUDED_URLS_PARAMETER_NAME => [
                                'http://example.com/foo'
                            ],
                        ]),
                    ]));
                },
                'setUp' => function (Task $task, string $webPageContent) {
                    $this->setSuccessfulTaskPerformerWebPageRetrieverOnTaskPerformer(
                        LinkIntegrityTaskTypePerformer::class,
                        $task,
                        $webPageContent
                    );
                },
                'webPageContent' => '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>',
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
            'excluded domains, no sources' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);

                    return $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
                        'type' => $this->getTaskTypeString(),
                        'parameters' => json_encode([
                            LinkCheckerConfigurationFactory::EXCLUDED_DOMAINS_PARAMETER_NAME => [
                                'example.com'
                            ],
                        ]),
                    ]));
                },
                'setUp' => function (Task $task, string $webPageContent) {
                    $this->setSuccessfulTaskPerformerWebPageRetrieverOnTaskPerformer(
                        LinkIntegrityTaskTypePerformer::class,
                        $task,
                        $webPageContent
                    );
                },
                'webPageContent' => '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>',
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
            'ignored schemes, no sources' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);

                    return $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
                        'type' => $this->getTaskTypeString(),
                    ]));
                },
                'setUp' => function (Task $task, string $webPageContent) {
                    $this->setSuccessfulTaskPerformerWebPageRetrieverOnTaskPerformer(
                        LinkIntegrityTaskTypePerformer::class,
                        $task,
                        $webPageContent
                    );
                },
                'webPageContent' => HtmlDocumentFactory::load('ignored-link-integrity-schemes'),
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
        ];
    }

    /**
     * @dataProvider cookiesDataProvider
     */
    public function testSetCookiesOnRequests(array $taskParameters, string $expectedRequestCookieHeader)
    {
        $httpFixtures = [
            new Response(200),
        ];

        $this->httpMockHandler->appendFixtures($httpFixtures);

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters)
        ]));

        $this->setSuccessfulTaskPerformerWebPageRetrieverOnTaskPerformer(
            LinkIntegrityTaskTypePerformer::class,
            $task,
            '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>'
        );

        $this->taskTypePerformer->perform($task);

        $this->assertCookieHeadeSetOnAllRequests(count($httpFixtures), $expectedRequestCookieHeader);
    }

    /**
     * @dataProvider httpAuthDataProvider
     */
    public function testSetHttpAuthenticationOnRequests(
        array $taskParameters,
        string $expectedRequestAuthorizationHeaderValue
    ) {
        $httpFixtures = [
            new Response(200),
        ];

        $this->httpMockHandler->appendFixtures($httpFixtures);

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters)
        ]));

        $this->setSuccessfulTaskPerformerWebPageRetrieverOnTaskPerformer(
            LinkIntegrityTaskTypePerformer::class,
            $task,
            '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>'
        );

        $this->taskTypePerformer->perform($task);

        $this->assertHttpAuthorizationSetOnAllRequests(count($httpFixtures), $expectedRequestAuthorizationHeaderValue);
    }

    public function testHandles()
    {
        $this->assertFalse($this->taskTypePerformer->handles(TypeInterface::TYPE_HTML_VALIDATION));
        $this->assertFalse($this->taskTypePerformer->handles(TypeInterface::TYPE_CSS_VALIDATION));
        $this->assertTrue($this->taskTypePerformer->handles(TypeInterface::TYPE_LINK_INTEGRITY));
        $this->assertFalse($this->taskTypePerformer->handles(TypeInterface::TYPE_LINK_INTEGRITY_SINGLE_URL));
        $this->assertFalse($this->taskTypePerformer->handles(TypeInterface::TYPE_URL_DISCOVERY));
    }

    public function testGetPriority()
    {
        $this->assertEquals(
            self::$container->getParameter('link_integrity_task_type_performer_priority'),
            $this->taskTypePerformer->getPriority()
        );
    }
}
