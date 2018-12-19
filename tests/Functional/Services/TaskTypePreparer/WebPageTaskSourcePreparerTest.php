<?php

namespace App\Tests\Functional\Services\TaskTypePreparer;

use App\Entity\CachedResource;
use App\Entity\Task\Task;
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

    /**
     * @dataProvider prepareInvalidContentTypeDataProvider
     *
     * @param string $contentType
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
}
