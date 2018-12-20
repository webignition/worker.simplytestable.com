<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services\TaskTypePreparer;

use App\Entity\CachedResource;
use App\Entity\Task\Task;
use App\Model\Source;
use App\Model\Task\Type;
use App\Model\Task\TypeInterface;
use App\Services\SourceFactory;
use App\Services\TaskTypePreparer\WebPageTaskSourcePreparer;
use App\Services\TaskTypeService;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\HttpMockHandler;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use GuzzleHttp\Psr7\Response;

class WebPageTaskSourcePreparerTest extends AbstractBaseTestCase
{
    /**
     * @var WebPageTaskSourcePreparer
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

        $this->preparer = self::$container->get(WebPageTaskSourcePreparer::class);
        $this->sourceFactory = self::$container->get(SourceFactory::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);

        $entityManager = self::$container->get(EntityManagerInterface::class);
        $this->cachedResourceRepository = $entityManager->getRepository(CachedResource::class);
    }

    public function testHandles()
    {
        $this->assertTrue($this->preparer->handles(TypeInterface::TYPE_HTML_VALIDATION));
        $this->assertTrue($this->preparer->handles(TypeInterface::TYPE_CSS_VALIDATION));
        $this->assertTrue($this->preparer->handles(TypeInterface::TYPE_LINK_INTEGRITY));
        $this->assertTrue($this->preparer->handles(TypeInterface::TYPE_LINK_INTEGRITY_SINGLE_URL));
        $this->assertTrue($this->preparer->handles(TypeInterface::TYPE_URL_DISCOVERY));
    }

    public function testGetPriority()
    {
        $this->assertEquals(
            self::$container->getParameter('web_page_task_source_preparer_priority'),
            $this->preparer->getPriority()
        );
    }

    /**
     * @dataProvider prepareInvalidContentTypeDataProvider
     */
    public function testPrepareInvalidContentType(string $contentType)
    {
        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => $contentType]),
        ]);

        $taskTypeService = self::$container->get(TaskTypeService::class);

        $url = 'http://example.com';
        $task = Task::create($taskTypeService->get(Type::TYPE_HTML_VALIDATION), $url);

        $this->assertEquals([], $task->getSources());

        $this->preparer->prepare($task);

        $expectedSource = $this->sourceFactory->createInvalidSource($url, 'invalid-content-type');

        $this->assertEquals(
            [
                $url => $expectedSource,
            ],
            $task->getSources()
        );
    }

    public function prepareInvalidContentTypeDataProvider(): array
    {
        return [
            'disallowed content type' => [
                'contentType' => 'text/plain',
            ],
            'unparseable content type' => [
                'contentType' => 'f o o',
            ],
        ];
    }

    public function testPrepareSuccessNoPreExistingCachedResource()
    {
        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], 'html content'),
        ]);

        $taskTypeService = self::$container->get(TaskTypeService::class);

        $url = 'http://example.com';
        $task = Task::create($taskTypeService->get(Type::TYPE_HTML_VALIDATION), $url);

        $this->assertEquals([], $task->getSources());
        $this->assertEquals([], $this->cachedResourceRepository->findAll());

        $this->preparer->prepare($task);

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
    }

    public function testPrepareSuccessHasPreExistingCachedResource()
    {
        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], 'html content'),
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], 'html content'),
        ]);

        $taskTypeService = self::$container->get(TaskTypeService::class);

        $url = 'http://example.com';
        $task = Task::create($taskTypeService->get(Type::TYPE_HTML_VALIDATION), $url);

        $this->assertEquals([], $task->getSources());
        $this->assertEquals([], $this->cachedResourceRepository->findAll());

        $this->preparer->prepare($task);

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

        $this->preparer->prepare($task);
    }

    /**
     * @dataProvider prepareTooManyRedirectsDataProvider
     */
    public function testPrepareTooManyRedirects(array $httpFixtures, array $expectedSourceData)
    {
        $this->httpMockHandler->appendFixtures($httpFixtures);

        $taskTypeService = self::$container->get(TaskTypeService::class);

        $url = 'http://example.com';
        $task = Task::create($taskTypeService->get(Type::TYPE_HTML_VALIDATION), $url);

        $this->assertEquals([], $task->getSources());

        $this->preparer->prepare($task);

        /* @var Source $source */
        $source = $task->getSources()[$url];

        $this->assertEquals($expectedSourceData, $source->toArray());
    }

    public function prepareTooManyRedirectsDataProvider(): array
    {
        return [
            'not redirect loop (first 6 responses are to HEAD requests, second 6 are to GET requests)' => [
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
            'is redirect loop' => [
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
        ];
    }
}
