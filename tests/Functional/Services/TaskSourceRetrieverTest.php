<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services;

use App\Entity\CachedResource;
use App\Entity\Task\Task;
use App\Model\Source;
use App\Model\Task\Type;
use App\Model\Task\TypeInterface;
use App\Services\CachedResourceFactory;
use App\Services\CachedResourceManager;
use App\Services\RequestIdentifierFactory;
use App\Services\SourceFactory;
use App\Services\TaskSourceRetriever;
use App\Services\TaskTypeService;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\HttpMockHandler;
use App\Tests\Services\ObjectReflector;
use App\Tests\Services\TestTaskFactory;
use App\Tests\UnhandledGuzzleException;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Lock\Factory as LockFactory;
use Symfony\Component\Lock\LockInterface;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;
use webignition\WebResource\Retriever;
use webignition\WebResource\WebPage\WebPage;

class TaskSourceRetrieverTest extends AbstractBaseTestCase
{
    /**
     * @var TaskSourceRetriever
     */
    private $taskSourceRetriever;

    /**
     * @var SourceFactory
     */
    private $sourceFactory;

    /**
     * @var EntityRepository|ObjectRepository
     */
    private $cachedResourceRepository;

    /**
     * @var HttpMockHandler
     */
    private $httpMockHandler;

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

        $this->taskSourceRetriever = self::$container->get(TaskSourceRetriever::class);
        $this->sourceFactory = self::$container->get(SourceFactory::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);

        $entityManager = self::$container->get(EntityManagerInterface::class);
        $this->cachedResourceRepository = $entityManager->getRepository(CachedResource::class);
        $this->taskTypeService = self::$container->get(TaskTypeService::class);
    }

    public function testRetrieveCannotAcquireLock()
    {
        $lock = \Mockery::mock(LockInterface::class);
        $lock
            ->shouldReceive('acquire')
            ->andReturn(false);

        $lockFactory = \Mockery::mock(LockFactory::class);
        $lockFactory
            ->shouldReceive('createLock')
            ->andReturn($lock);

        ObjectReflector::setProperty(
            $this->taskSourceRetriever,
            TaskSourceRetriever::class,
            'lockFactory',
            $lockFactory
        );

        /* @var Retriever $retriever */
        $retriever = self::$container->get('app.services.web-resource-retriever.web-page');

        $task = Task::create($this->getHtmlValidationTaskType(), 'http://example.com');

        $retrieveResult = $this->taskSourceRetriever->retrieve($retriever, $task, $task->getUrl());
        $this->assertFalse($retrieveResult);
    }

    /**
     * @dataProvider retrieveInvalidContentTypeDataProvider
     */
    public function testRetrieveInvalidContentType(
        string $contentType,
        Source $expectedSource
    ) {
        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => $contentType]),
        ]);

        /* @var Retriever $retriever */
        $retriever = self::$container->get('app.services.web-resource-retriever.web-page');

        $url = 'http://example.com';
        $task = Task::create($this->getHtmlValidationTaskType(), $url);

        $this->assertEquals([], $task->getSources());

        $retrieveResult = $this->taskSourceRetriever->retrieve($retriever, $task, $task->getUrl());
        $this->assertTrue($retrieveResult);

        $this->assertEquals(
            [
                $url => $expectedSource,
            ],
            $task->getSources()
        );
    }

    public function retrieveInvalidContentTypeDataProvider(): array
    {
        return [
            'disallowed content type' => [
                'contentType' => 'text/plain',
                'expectedSource' => new Source(
                    'http://example.com',
                    Source::TYPE_INVALID,
                    'invalid:invalid-content-type:text/plain'
                )
            ],
            'unparseable content type' => [
                'contentType' => 'f o o',
                'expectedSource' => new Source(
                    'http://example.com',
                    Source::TYPE_INVALID,
                    'invalid:invalid-content-type:f o o'
                )
            ],
        ];
    }

    /**
     * @dataProvider retrieverSuccessNoPreExistingCachedResourceForWebPageRetrieverDataProvider
     */
    public function testRetrieveSuccessNoPreExistingCachedResourceForWebPageRetriever(
        string $contentType,
        array $sourceContext = []
    ) {
        $this->assertRetrieveSuccessNoPreExistingCachedResource(
            self::$container->get('app.services.web-resource-retriever.web-page'),
            $contentType,
            $sourceContext
        );
    }

    public function retrieverSuccessNoPreExistingCachedResourceForWebPageRetrieverDataProvider(): array
    {
        return [
            'text/html' => [
                'contentType' => 'text/html',
            ],
            'text/html; charset=utf-8' => [
                'contentType' => 'text/html; charset=utf-8',
            ],
            'text/html; charset=windows-1251' => [
                'contentType' => 'text/html; charset=windows-1251',
            ],
        ];
    }

    /**
     * @dataProvider retrieverSuccessNoPreExistingCachedResourceForCssRetrieverDataProvider
     */
    public function testRetrieveSuccessNoPreExistingCachedResourceForCssRetriever(
        string $contentType,
        array $sourceContext = []
    ) {
        $this->assertRetrieveSuccessNoPreExistingCachedResource(
            self::$container->get('app.services.web-resource-retriever.css'),
            $contentType,
            $sourceContext
        );
    }

    public function retrieverSuccessNoPreExistingCachedResourceForCssRetrieverDataProvider(): array
    {
        return [
            'text/css, no context' => [
                'contentType' => 'text/css',
            ],
            'text/css, resource context' => [
                'contentType' => 'text/css',
                'sourceContext' => [
                    'origin' => 'resource',
                ],
            ],
            'text/css, import context' => [
                'contentType' => 'text/css',
                'sourceContext' => [
                    'origin' => 'import',
                ],
            ],
        ];
    }

    public function assertRetrieveSuccessNoPreExistingCachedResource(
        Retriever $retriever,
        string $contentType,
        array $sourceContext = []
    ) {
        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => $contentType]),
            new Response(200, ['content-type' => $contentType], 'html content'),
        ]);

        $url = 'http://example.com';
        $task = Task::create($this->getHtmlValidationTaskType(), $url);

        $this->assertEquals([], $task->getSources());
        $this->assertEquals([], $this->cachedResourceRepository->findAll());

        $retrieveResult = $this->taskSourceRetriever->retrieve($retriever, $task, $task->getUrl(), $sourceContext);
        $this->assertTrue($retrieveResult);

        /* @var CachedResource $cachedResource */
        $cachedResource = $this->cachedResourceRepository->findOneBy([
            'url' => $url,
        ]);

        if ($cachedResource instanceof CachedResource) {
            $this->assertEquals($contentType, (string) $cachedResource->getContentType());

            $expectedSource = $this->sourceFactory->fromCachedResource($cachedResource, $sourceContext);

            $this->assertEquals(
                [
                    $url => $expectedSource,
                ],
                $task->getSources()
            );
        }
    }

    public function testRetrieveSuccessHasPreExistingSource()
    {
        $retrieverServiceId = 'app.services.web-resource-retriever.web-page';

        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], 'html content'),
        ]);

        /* @var Retriever $retriever */
        $retriever = self::$container->get($retrieverServiceId);

        $url = 'http://example.com';
        $task = Task::create($this->getHtmlValidationTaskType(), $url);

        $this->assertEquals([], $task->getSources());
        $this->assertEquals([], $this->cachedResourceRepository->findAll());

        $retrieveResult = $this->taskSourceRetriever->retrieve($retriever, $task, $task->getUrl());
        $this->assertTrue($retrieveResult);

        /* @var CachedResource $cachedResource */
        $cachedResource = $this->cachedResourceRepository->findOneBy([
            'url' => $url,
        ]);

        if ($cachedResource instanceof CachedResource) {
            $expectedSource = $this->sourceFactory->fromCachedResource($cachedResource);

            $this->assertEquals(
                [
                    $url => $expectedSource,
                ],
                $task->getSources()
            );

            $this->taskSourceRetriever->retrieve($retriever, $task, $task->getUrl());

            $this->assertEquals(
                [
                    $url => $expectedSource,
                ],
                $task->getSources()
            );
        }
    }

    public function testRetrieveSuccessHasPreExistingCachedResource()
    {
        $retrieverServiceId = 'app.services.web-resource-retriever.web-page';

        /* @var Retriever $retriever */
        $retriever = self::$container->get($retrieverServiceId);
        $requestIdentifierFactory = self::$container->get(RequestIdentifierFactory::class);
        $cachedResourceFactory = self::$container->get(CachedResourceFactory::class);
        $cachedResourceManager = self::$container->get(CachedResourceManager::class);

        $url = 'http://example.com';
        $task = Task::create($this->getHtmlValidationTaskType(), $url);

        $this->assertEquals([], $task->getSources());

        $requestIdentifier = $requestIdentifierFactory->createFromTaskResource($task, $url);
        $requestHash = (string) $requestIdentifier;
        $resource = WebPage::createFromContent('html content');

        $cachedResource = $cachedResourceFactory->create($requestHash, $url, $resource);
        $cachedResourceManager->persist($cachedResource);

        $this->assertEquals([$cachedResource], $this->cachedResourceRepository->findAll());

        $retrieveResult = $this->taskSourceRetriever->retrieve($retriever, $task, $task->getUrl());
        $this->assertTrue($retrieveResult);

        $expectedSource = $this->sourceFactory->fromCachedResource($cachedResource);

        $this->assertEquals(
            [
                $url => $expectedSource,
            ],
            $task->getSources()
        );
    }

    /**
     * @dataProvider retrieveFailureDataProvider
     */
    public function testRetrieveFailure(array $httpFixtures, array $expectedSourceData)
    {
        $retrieverServiceId = 'app.services.web-resource-retriever.web-page';

        $this->httpMockHandler->appendFixtures($httpFixtures);

        /* @var Retriever $retriever */
        $retriever = self::$container->get($retrieverServiceId);

        $url = 'http://example.com';
        $task = Task::create($this->getHtmlValidationTaskType(), $url);

        $this->assertEquals([], $task->getSources());

        $retrieveResult = $this->taskSourceRetriever->retrieve($retriever, $task, $task->getUrl());
        $this->assertTrue($retrieveResult);

        /* @var Source $source */
        $source = $task->getSources()[$url];

        $this->assertEquals($expectedSourceData, $source->toArray());
    }

    public function retrieveFailureDataProvider(): array
    {
        $http404Response = new Response(404);
        $curl28ConnectException = ConnectExceptionFactory::create('CURL/28 Operation timed out.');
        $unhandledGuzzleException = new UnhandledGuzzleException();

        return [
            'http 404' => [
                'httpFixtures' => [
                    $http404Response,
                    $http404Response,
                ],
                'expectedSourceData' => [
                    'url' => 'http://example.com',
                    'type' => Source::TYPE_UNAVAILABLE,
                    'value' => 'http:404',
                    'context' => [],
                ],
            ],
            'curl 28' => [
                'httpFixtures' => array_fill(0, 12, $curl28ConnectException),
                'expectedSourceData' => [
                    'url' => 'http://example.com',
                    'type' => Source::TYPE_UNAVAILABLE,
                    'value' => 'curl:28',
                    'context' => [],
                ],
            ],
            'http 301, not redirect loop (first 6 responses are to HEAD requests, second 6 are to GET requests)' => [
                'httpFixtures' => [
                    new Response(301, ['location' => 'http://example.com/1']),
                    new Response(301, ['location' => 'http://example.com/2']),
                    new Response(301, ['location' => 'http://example.com/3']),
                    new Response(301, ['location' => 'http://example.com/4']),
                    new Response(301, ['location' => 'http://example.com/5']),
                    new Response(301, ['location' => 'http://example.com/6']),
                    new Response(301, ['location' => 'http://example.com/1']),
                    new Response(301, ['location' => 'http://example.com/2']),
                    new Response(301, ['location' => 'http://example.com/3']),
                    new Response(301, ['location' => 'http://example.com/4']),
                    new Response(301, ['location' => 'http://example.com/5']),
                    new Response(301, ['location' => 'http://example.com/6']),
                ],
                'expectedSourceData' => [
                    'url' => 'http://example.com',
                    'type' => Source::TYPE_UNAVAILABLE,
                    'value' => 'http:301',
                    'context' => [
                        'too_many_redirects' => true,
                        'is_redirect_loop' => false,
                        'history' => [
                            'http://example.com',
                            'http://example.com/1',
                            'http://example.com/2',
                            'http://example.com/3',
                            'http://example.com/4',
                            'http://example.com/5',
                            'http://example.com',
                            'http://example.com/1',
                            'http://example.com/2',
                            'http://example.com/3',
                            'http://example.com/4',
                            'http://example.com/5',
                        ],
                    ],
                ],
            ],
            'http 301,  redirect loop' => [
                'httpFixtures' => [
                    new Response(301, ['location' => 'http://example.com/1']),
                    new Response(301, ['location' => 'http://example.com/2']),
                    new Response(301, ['location' => 'http://example.com/3']),
                    new Response(301, ['location' => 'http://example.com/1']),
                    new Response(301, ['location' => 'http://example.com/2']),
                    new Response(301, ['location' => 'http://example.com/3']),
                    new Response(301, ['location' => 'http://example.com/1']),
                    new Response(301, ['location' => 'http://example.com/2']),
                    new Response(301, ['location' => 'http://example.com/3']),
                    new Response(301, ['location' => 'http://example.com/1']),
                    new Response(301, ['location' => 'http://example.com/2']),
                    new Response(301, ['location' => 'http://example.com/3']),
                ],
                'expectedSourceData' => [
                    'url' => 'http://example.com',
                    'type' => Source::TYPE_UNAVAILABLE,
                    'value' => 'http:301',
                    'context' => [
                        'too_many_redirects' => true,
                        'is_redirect_loop' => true,
                        'history' => [
                            'http://example.com',
                            'http://example.com/1',
                            'http://example.com/2',
                            'http://example.com/3',
                            'http://example.com/1',
                            'http://example.com/2',
                            'http://example.com',
                            'http://example.com/1',
                            'http://example.com/2',
                            'http://example.com/3',
                            'http://example.com/1',
                            'http://example.com/2',
                        ],
                    ],
                ],
            ],
            'unknown' => [
                'httpFixtures' => [
                    $unhandledGuzzleException,
                    $unhandledGuzzleException,
                ],
                'expectedSourceData' => [
                    'url' => 'http://example.com',
                    'type' => Source::TYPE_UNAVAILABLE,
                    'value' => 'unknown:0',
                    'context' => [],
                ],
            ],
        ];
    }

    /**
     * @dataProvider cookiesDataProvider
     */
    public function testSetCookiesOnRequests(array $taskParameters, string $expectedRequestCookieHeader)
    {
        $retrieverServiceId = 'app.services.web-resource-retriever.web-page';

        $httpFixtures = [
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], 'html content'),
        ];

        $this->httpMockHandler->appendFixtures($httpFixtures);

        $testTaskFactory = self::$container->get(TestTaskFactory::class);
        $httpHistoryContainer = self::$container->get(HttpHistoryContainer::class);

        /* @var Retriever $retriever */
        $retriever = self::$container->get($retrieverServiceId);

        $task = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'parameters' => json_encode($taskParameters),
        ]));

        $this->assertEquals([], $task->getSources());
        $this->assertEquals([], $this->cachedResourceRepository->findAll());

        $this->taskSourceRetriever->retrieve($retriever, $task, $task->getUrl());

        /* @var array $historicalRequests */
        $historicalRequests = $httpHistoryContainer->getRequests();
        $this->assertCount(count($httpFixtures), $historicalRequests);

        foreach ($historicalRequests as $historicalRequest) {
            $cookieHeaderLine = $historicalRequest->getHeaderLine('cookie');
            $this->assertEquals($expectedRequestCookieHeader, $cookieHeaderLine);
        }
    }

    public function cookiesDataProvider(): array
    {
        return [
            'no cookies' => [
                'taskParameters' => [],
                'expectedRequestCookieHeader' => '',
            ],
            'single cookie' => [
                'taskParameters' => [
                    'cookies' => [
                        [
                            'Name' => 'foo',
                            'Value' => 'bar',
                            'Domain' => '.example.com',
                        ],
                    ],
                ],
                'expectedRequestCookieHeader' => 'foo=bar',
            ],
            'multiple cookies' => [
                'taskParameters' => [
                    'cookies' => [
                        [
                            'Name' => 'foo1',
                            'Value' => 'bar1',
                            'Domain' => '.example.com',
                        ],
                        [
                            'Name' => 'foo2',
                            'Value' => 'bar2',
                            'Domain' => 'foo2.example.com',
                        ],
                        [
                            'Name' => 'foo3',
                            'Value' => 'bar3',
                            'Domain' => '.example.com',
                        ],
                    ],
                ],
                'expectedRequestCookieHeader' => 'foo1=bar1; foo3=bar3',
            ],
        ];
    }

    /**
     * @dataProvider httpAuthDataProvider
     */
    public function testSetHttpAuthenticationOnRequests(
        array $taskParameters,
        string $expectedRequestAuthorizationHeaderValue
    ) {
        $retrieverServiceId = 'app.services.web-resource-retriever.web-page';

        $httpFixtures = [
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], 'html content'),
        ];

        $this->httpMockHandler->appendFixtures($httpFixtures);

        $testTaskFactory = self::$container->get(TestTaskFactory::class);
        $httpHistoryContainer = self::$container->get(HttpHistoryContainer::class);

        /* @var Retriever $retriever */
        $retriever = self::$container->get($retrieverServiceId);

        $task = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'parameters' => json_encode($taskParameters),
        ]));

        $this->assertEquals([], $task->getSources());
        $this->assertEquals([], $this->cachedResourceRepository->findAll());

        $this->taskSourceRetriever->retrieve($retriever, $task, $task->getUrl());

        /* @var array $historicalRequests */
        $historicalRequests = $httpHistoryContainer->getRequests();
        $this->assertCount(count($httpFixtures), $historicalRequests);

        foreach ($historicalRequests as $historicalRequest) {
            $authorizationHeaderLine = $historicalRequest->getHeaderLine('authorization');

            $decodedAuthorizationHeaderValue = base64_decode(
                str_replace('Basic ', '', $authorizationHeaderLine)
            );

            $this->assertEquals($expectedRequestAuthorizationHeaderValue, $decodedAuthorizationHeaderValue);
        }
    }

    public function httpAuthDataProvider(): array
    {
        return [
            'no auth' => [
                'taskParameters' => [],
                'expectedRequestAuthorizationHeaderValue' => '',
            ],
            'has auth' => [
                'taskParameters' => [
                    'http-auth-username' => 'foouser',
                    'http-auth-password' => 'foopassword',
                ],
                'expectedRequestAuthorizationHeaderValue' => 'foouser:foopassword',
            ],
        ];
    }

    private function getHtmlValidationTaskType(): Type
    {
        $type = self::$container->get(TaskTypeService::class)->get(TypeInterface::TYPE_HTML_VALIDATION);

        if (!$type instanceof Type) {
            throw new \RuntimeException();
        }

        return $type;
    }
}
