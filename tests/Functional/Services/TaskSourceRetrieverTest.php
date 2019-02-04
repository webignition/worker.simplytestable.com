<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services;

use App\Entity\CachedResource;
use App\Entity\Task\Task;
use App\Model\Source;
use App\Model\Task\Type;
use App\Services\SourceFactory;
use App\Services\TaskSourceRetriever;
use App\Services\TaskTypeService;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\HttpMockHandler;
use App\Tests\UnhandledGuzzleException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use GuzzleHttp\Psr7\Response;
use webignition\WebResource\Retriever;

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
     * @var EntityRepository
     */
    private $cachedResourceRepository;

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

        $this->taskSourceRetriever = self::$container->get(TaskSourceRetriever::class);
        $this->sourceFactory = self::$container->get(SourceFactory::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);

        $entityManager = self::$container->get(EntityManagerInterface::class);
        $this->cachedResourceRepository = $entityManager->getRepository(CachedResource::class);
    }

    /**
     * @dataProvider retrieveInvalidContentTypeDataProvider
     */
    public function testRetrieveInvalidContentType(string $retrieverServiceId, string $contentType)
    {
        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => $contentType]),
        ]);

        $taskTypeService = self::$container->get(TaskTypeService::class);

        /* @var Retriever $retriever */
        $retriever = self::$container->get($retrieverServiceId);

        $url = 'http://example.com';
        $task = Task::create($taskTypeService->get(Type::TYPE_HTML_VALIDATION), $url);

        $this->assertEquals([], $task->getSources());

        $this->taskSourceRetriever->retrieve($retriever, $task, $task->getUrl());

        $expectedSource = $this->sourceFactory->createInvalidSource($url, 'invalid-content-type');

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
                'retrieverServiceId' => 'app.services.web-resource-retriever.web-page',
                'contentType' => 'text/plain',
            ],
            'unparseable content type' => [
                'retrieverServiceId' => 'app.services.web-resource-retriever.web-page',
                'contentType' => 'f o o',
            ],
        ];
    }

    /**
     * @dataProvider retrieverSuccessNoPreExistingCachedResourceDataProvider
     */
    public function testRetrieveSuccessNoPreExistingCachedResource(string $retrieverServiceId, string $contentType)
    {
        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => $contentType]),
            new Response(200, ['content-type' => $contentType], 'html content'),
        ]);

        $taskTypeService = self::$container->get(TaskTypeService::class);

        /* @var Retriever $retriever */
        $retriever = self::$container->get($retrieverServiceId);

        $url = 'http://example.com';
        $task = Task::create($taskTypeService->get(Type::TYPE_HTML_VALIDATION), $url);

        $this->assertEquals([], $task->getSources());
        $this->assertEquals([], $this->cachedResourceRepository->findAll());

        $this->taskSourceRetriever->retrieve($retriever, $task, $task->getUrl());

        /* @var CachedResource $cachedResource */
        $cachedResource = $this->cachedResourceRepository->findOneBy([
            'url' => $url,
        ]);

        $this->assertEquals($contentType, (string) $cachedResource->getContentType());

        $expectedSource = $this->sourceFactory->fromCachedResource($cachedResource);

        $this->assertEquals(
            [
                $url => $expectedSource,
            ],
            $task->getSources()
        );
    }

    public function retrieverSuccessNoPreExistingCachedResourceDataProvider(): array
    {
        return [
            'text/html' => [
                'retrieverServiceId' => 'app.services.web-resource-retriever.web-page',
                'contentType' => 'text/html',
            ],
            'text/html; charset=utf-8' => [
                'retrieverServiceId' => 'app.services.web-resource-retriever.web-page',
                'contentType' => 'text/html; charset=utf-8',
            ],
            'text/html; charset=windows-1251' => [
                'retrieverServiceId' => 'app.services.web-resource-retriever.web-page',
                'contentType' => 'text/html; charset=windows-1251',
            ],
        ];
    }

    public function testRetrieveSuccessHasPreExistingCachedResource()
    {
        $retrieverServiceId = 'app.services.web-resource-retriever.web-page';

        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], 'html content'),
        ]);

        $taskTypeService = self::$container->get(TaskTypeService::class);

        /* @var Retriever $retriever */
        $retriever = self::$container->get($retrieverServiceId);

        $url = 'http://example.com';
        $task = Task::create($taskTypeService->get(Type::TYPE_HTML_VALIDATION), $url);

        $this->assertEquals([], $task->getSources());
        $this->assertEquals([], $this->cachedResourceRepository->findAll());

        $this->taskSourceRetriever->retrieve($retriever, $task, $task->getUrl());

        /* @var CachedResource $cachedResource */
        $cachedResource = $this->cachedResourceRepository->findOneBy([
            'url' => $url,
        ]);

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

    /**
     * @dataProvider retrieveFailureDataProvider
     */
    public function testRetrieveFailure(array $httpFixtures, array $expectedSourceData)
    {
        $retrieverServiceId = 'app.services.web-resource-retriever.web-page';

        $this->httpMockHandler->appendFixtures($httpFixtures);

        $taskTypeService = self::$container->get(TaskTypeService::class);

        /* @var Retriever $retriever */
        $retriever = self::$container->get($retrieverServiceId);

        $url = 'http://example.com';
        $task = Task::create($taskTypeService->get(Type::TYPE_HTML_VALIDATION), $url);

        $this->assertEquals([], $task->getSources());

        $this->taskSourceRetriever->retrieve($retriever, $task, $task->getUrl());

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
}
