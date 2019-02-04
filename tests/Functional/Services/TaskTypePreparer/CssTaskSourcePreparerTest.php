<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services\TaskTypePreparer;

use App\Entity\CachedResource;
use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Model\Source;
use App\Model\Task\Type;
use App\Services\SourceFactory;
use App\Services\TaskTypePreparer\CssTaskSourcePreparer;
use App\Services\TaskTypePreparer\WebPageTaskSourcePreparer;
use App\Services\TaskTypeService;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\HttpMockHandler;
use App\Tests\UnhandledGuzzleException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use GuzzleHttp\Psr7\Response;

class CssTaskSourcePreparerTest extends AbstractBaseTestCase
{
    /**
     * @var CssTaskSourcePreparer
     */
    private $preparer;

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

        $this->preparer = self::$container->get(CssTaskSourcePreparer::class);
        $this->sourceFactory = self::$container->get(SourceFactory::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);

        $entityManager = self::$container->get(EntityManagerInterface::class);
        $this->cachedResourceRepository = $entityManager->getRepository(CachedResource::class);
    }

    /**
     * @dataProvider prepareWrongTaskTypeDataProvider
     */
    public function testPrepareWrongTaskType(string $taskType)
    {
        $taskTypeService = self::$container->get(TaskTypeService::class);

        $task = Task::create($taskTypeService->get($taskType), 'http://example.com/');

        $state = $task->getState();
        $sources = $task->getSources();

        $this->preparer->prepare($task);

        $this->assertEquals($state, $task->getState());
        $this->assertEquals($sources, $task->getSources());
    }

    public function prepareWrongTaskTypeDataProvider(): array
    {
        return [
            'html validation' => [
                'taskType' => Type::TYPE_HTML_VALIDATION,
            ],
            'link integrity' => [
                'taskType' => Type::TYPE_LINK_INTEGRITY,
            ],
            'link integrity single url' => [
                'taskType' => Type::TYPE_LINK_INTEGRITY_SINGLE_URL,
            ],
            'url disovery' => [
                'taskType' => Type::TYPE_URL_DISCOVERY,
            ],
        ];
    }

//    /**
//     * @dataProvider prepareInvalidContentTypeDataProvider
//     */
//    public function testPrepareInvalidContentType(string $contentType)
//    {
//        $this->httpMockHandler->appendFixtures([
//            new Response(200, ['content-type' => $contentType]),
//        ]);
//
//        $taskTypeService = self::$container->get(TaskTypeService::class);
//
//        $url = 'http://example.com';
//        $task = Task::create($taskTypeService->get(Type::TYPE_HTML_VALIDATION), $url);
//
//        $this->assertEquals([], $task->getSources());
//
//        $this->preparer->prepare($task);
//
//        $expectedSource = $this->sourceFactory->createInvalidSource($url, 'invalid-content-type');
//
//        $this->assertEquals(
//            [
//                $url => $expectedSource,
//            ],
//            $task->getSources()
//        );
//    }
//
//    public function prepareInvalidContentTypeDataProvider(): array
//    {
//        return [
//            'disallowed content type' => [
//                'contentType' => 'text/plain',
//            ],
//            'unparseable content type' => [
//                'contentType' => 'f o o',
//            ],
//        ];
//    }
//
//    /**
//     * @dataProvider prepareSuccessNoPreExistingCachedResourceDataProvider
//     */
//    public function testPrepareSuccessNoPreExistingCachedResource(string $contentType)
//    {
//        $this->httpMockHandler->appendFixtures([
//            new Response(200, ['content-type' => $contentType]),
//            new Response(200, ['content-type' => $contentType], 'html content'),
//        ]);
//
//        $taskTypeService = self::$container->get(TaskTypeService::class);
//
//        $url = 'http://example.com';
//        $task = Task::create($taskTypeService->get(Type::TYPE_HTML_VALIDATION), $url);
//
//        $this->assertEquals([], $task->getSources());
//        $this->assertEquals([], $this->cachedResourceRepository->findAll());
//
//        $this->preparer->prepare($task);
//
//        /* @var CachedResource $cachedResource */
//        $cachedResource = $this->cachedResourceRepository->findOneBy([
//            'url' => $url,
//        ]);
//
//        $this->assertEquals($contentType, (string) $cachedResource->getContentType());
//
//        $expectedSource = $this->sourceFactory->fromCachedResource($cachedResource);
//
//        $this->assertEquals(
//            [
//                $url => $expectedSource,
//            ],
//            $task->getSources()
//        );
//    }
//
//    public function prepareSuccessNoPreExistingCachedResourceDataProvider(): array
//    {
//        return [
//            'text/html' => [
//                'contentType' => 'text/html',
//            ],
//            'text/html; charset=utf-8' => [
//                'contentType' => 'text/html; charset=utf-8',
//            ],
//            'text/html; charset=windows-1251' => [
//                'contentType' => 'text/html; charset=windows-1251',
//            ],
//        ];
//    }
//
//    public function testPrepareSuccessHasPreExistingCachedResource()
//    {
//        $this->httpMockHandler->appendFixtures([
//            new Response(200, ['content-type' => 'text/html']),
//            new Response(200, ['content-type' => 'text/html'], 'html content'),
//        ]);
//
//        $taskTypeService = self::$container->get(TaskTypeService::class);
//
//        $url = 'http://example.com';
//        $task = Task::create($taskTypeService->get(Type::TYPE_HTML_VALIDATION), $url);
//
//        $this->assertEquals([], $task->getSources());
//        $this->assertEquals([], $this->cachedResourceRepository->findAll());
//
//        $this->preparer->prepare($task);
//
//        /* @var CachedResource $cachedResource */
//        $cachedResource = $this->cachedResourceRepository->findOneBy([
//            'url' => $url,
//        ]);
//
//        $expectedSource = $this->sourceFactory->fromCachedResource($cachedResource);
//
//        $this->assertEquals(
//            [
//                $url => $expectedSource,
//            ],
//            $task->getSources()
//        );
//
//        $this->preparer->prepare($task);
//
//        $this->assertEquals(
//            [
//                $url => $expectedSource,
//            ],
//            $task->getSources()
//        );
//    }
//
//    /**
//     * @dataProvider prepareFailureDataProvider
//     */
//    public function testPrepareFailure(array $httpFixtures, array $expectedSourceData)
//    {
//        $this->httpMockHandler->appendFixtures($httpFixtures);
//
//        $taskTypeService = self::$container->get(TaskTypeService::class);
//
//        $url = 'http://example.com';
//        $task = Task::create($taskTypeService->get(Type::TYPE_HTML_VALIDATION), $url);
//
//        $this->assertEquals([], $task->getSources());
//
//        $this->preparer->prepare($task);
//
//        /* @var Source $source */
//        $source = $task->getSources()[$url];
//
//        $this->assertEquals($expectedSourceData, $source->toArray());
//    }
//
//    public function prepareFailureDataProvider(): array
//    {
//        $http404Response = new Response(404);
//        $curl28ConnectException = ConnectExceptionFactory::create('CURL/28 Operation timed out.');
//        $unhandledGuzzleException = new UnhandledGuzzleException();
//
//        return [
//            'http 404' => [
//                'httpFixtures' => [
//                    $http404Response,
//                    $http404Response,
//                ],
//                'expectedSourceData' => [
//                    'url' => 'http://example.com',
//                    'type' => Source::TYPE_UNAVAILABLE,
//                    'value' => 'http:404',
//                    'context' => [],
//                ],
//            ],
//            'curl 28' => [
//                'httpFixtures' => array_fill(0, 12, $curl28ConnectException),
//                'expectedSourceData' => [
//                    'url' => 'http://example.com',
//                    'type' => Source::TYPE_UNAVAILABLE,
//                    'value' => 'curl:28',
//                    'context' => [],
//                ],
//            ],
//            'http 301, not redirect loop (first 6 responses are to HEAD requests, second 6 are to GET requests)' => [
//                'httpFixtures' => [
//                    new Response(301, ['location' => 'http://example.com/1']),
//                    new Response(301, ['location' => 'http://example.com/2']),
//                    new Response(301, ['location' => 'http://example.com/3']),
//                    new Response(301, ['location' => 'http://example.com/4']),
//                    new Response(301, ['location' => 'http://example.com/5']),
//                    new Response(301, ['location' => 'http://example.com/6']),
//                    new Response(301, ['location' => 'http://example.com/1']),
//                    new Response(301, ['location' => 'http://example.com/2']),
//                    new Response(301, ['location' => 'http://example.com/3']),
//                    new Response(301, ['location' => 'http://example.com/4']),
//                    new Response(301, ['location' => 'http://example.com/5']),
//                    new Response(301, ['location' => 'http://example.com/6']),
//                ],
//                'expectedSourceData' => [
//                    'url' => 'http://example.com',
//                    'type' => Source::TYPE_UNAVAILABLE,
//                    'value' => 'http:301',
//                    'context' => [
//                        'too_many_redirects' => true,
//                        'is_redirect_loop' => false,
//                        'history' => [
//                            'http://example.com',
//                            'http://example.com/1',
//                            'http://example.com/2',
//                            'http://example.com/3',
//                            'http://example.com/4',
//                            'http://example.com/5',
//                            'http://example.com',
//                            'http://example.com/1',
//                            'http://example.com/2',
//                            'http://example.com/3',
//                            'http://example.com/4',
//                            'http://example.com/5',
//                        ],
//                    ],
//                ],
//            ],
//            'http 301,  redirect loop' => [
//                'httpFixtures' => [
//                    new Response(301, ['location' => 'http://example.com/1']),
//                    new Response(301, ['location' => 'http://example.com/2']),
//                    new Response(301, ['location' => 'http://example.com/3']),
//                    new Response(301, ['location' => 'http://example.com/1']),
//                    new Response(301, ['location' => 'http://example.com/2']),
//                    new Response(301, ['location' => 'http://example.com/3']),
//                    new Response(301, ['location' => 'http://example.com/1']),
//                    new Response(301, ['location' => 'http://example.com/2']),
//                    new Response(301, ['location' => 'http://example.com/3']),
//                    new Response(301, ['location' => 'http://example.com/1']),
//                    new Response(301, ['location' => 'http://example.com/2']),
//                    new Response(301, ['location' => 'http://example.com/3']),
//                ],
//                'expectedSourceData' => [
//                    'url' => 'http://example.com',
//                    'type' => Source::TYPE_UNAVAILABLE,
//                    'value' => 'http:301',
//                    'context' => [
//                        'too_many_redirects' => true,
//                        'is_redirect_loop' => true,
//                        'history' => [
//                            'http://example.com',
//                            'http://example.com/1',
//                            'http://example.com/2',
//                            'http://example.com/3',
//                            'http://example.com/1',
//                            'http://example.com/2',
//                            'http://example.com',
//                            'http://example.com/1',
//                            'http://example.com/2',
//                            'http://example.com/3',
//                            'http://example.com/1',
//                            'http://example.com/2',
//                        ],
//                    ],
//                ],
//            ],
//            'unknown' => [
//                'httpFixtures' => [
//                    $unhandledGuzzleException,
//                    $unhandledGuzzleException,
//                ],
//                'expectedSourceData' => [
//                    'url' => 'http://example.com',
//                    'type' => Source::TYPE_UNAVAILABLE,
//                    'value' => 'unknown:0',
//                    'context' => [],
//                ],
//            ],
//        ];
//    }
}
