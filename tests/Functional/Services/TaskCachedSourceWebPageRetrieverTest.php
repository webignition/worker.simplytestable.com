<?php

namespace App\Tests\Functional\Services;

use App\Entity\CachedResource;
use App\Entity\Task\Task;
use App\Model\Source;
use App\Model\Task\Type;
use App\Services\CachedResourceManager;
use App\Services\RequestIdentifierFactory;
use App\Services\SourceFactory;
use App\Services\TaskCachedSourceWebPageRetriever;
use App\Services\TaskTypeService;
use App\Tests\Functional\AbstractBaseTestCase;
use webignition\WebResource\WebPage\WebPage;

class TaskCachedSourceWebPageRetrieverTest extends AbstractBaseTestCase
{
    /**
     * @var TaskCachedSourceWebPageRetriever
     */
    private $taskCachedSourceWebPageRetriever;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskCachedSourceWebPageRetriever = self::$container->get(TaskCachedSourceWebPageRetriever::class);
    }

    public function testRetrieveNoSources()
    {
        $task = new Task();

        $this->assertNull($this->taskCachedSourceWebPageRetriever->retrieve($task));
    }

    public function testRetrievePrimarySourceIsNotCachedResource()
    {
        $taskTypeService = self::$container->get(TaskTypeService::class);
        $taskUrl = 'http://example.com';

        $source = new Source($taskUrl, Source::TYPE_UNAVAILABLE, 'http:404');

        $task = Task::create(
            $taskTypeService->get(Type::TYPE_HTML_VALIDATION),
            $taskUrl
        );

        $task->addSource($source);

        $this->assertNull($this->taskCachedSourceWebPageRetriever->retrieve($task));
    }

    public function testRetrieveNoPrimaryResource()
    {
        $taskTypeService = self::$container->get(TaskTypeService::class);
        $taskUrl = 'http://example.com';

        $source = new Source('http://example.com/foo', Source::TYPE_CACHED_RESOURCE, 'request-hash');

        $task = Task::create(
            $taskTypeService->get(Type::TYPE_HTML_VALIDATION),
            $taskUrl
        );

        $task->addSource($source);

        $this->assertNull($this->taskCachedSourceWebPageRetriever->retrieve($task));
    }

    public function testRetrieveInvalidCachedResource()
    {
        $taskTypeService = self::$container->get(TaskTypeService::class);
        $taskUrl = 'http://example.com';

        $source = new Source($taskUrl, Source::TYPE_CACHED_RESOURCE, 'invalid-request-hash');

        $task = Task::create(
            $taskTypeService->get(Type::TYPE_HTML_VALIDATION),
            $taskUrl
        );

        $task->addSource($source);

        $this->assertNull($this->taskCachedSourceWebPageRetriever->retrieve($task));
    }

    public function testRetrieveValidCachedResource()
    {
        $taskTypeService = self::$container->get(TaskTypeService::class);
        $sourceFactory = self::$container->get(SourceFactory::class);
        $requestIdentifierFactory = self::$container->get(RequestIdentifierFactory::class);
        $cachedResourceManager = self::$container->get(CachedResourceManager::class);

        $taskUrl = 'http://example.com';
        $webPageContent = 'web page content';

        $task = Task::create(
            $taskTypeService->get(Type::TYPE_HTML_VALIDATION),
            $taskUrl
        );

        $requestIdentifier = $requestIdentifierFactory->createFromTask($task);
        $cachedResource = CachedResource::create(
            (string) $requestIdentifier,
            $taskUrl,
            'text/html',
            $webPageContent
        );

        $cachedResourceManager->persist($cachedResource);

        $source = $sourceFactory->fromCachedResource($cachedResource);

        $task->addSource($source);

        $webPage = $this->taskCachedSourceWebPageRetriever->retrieve($task);

        $this->assertInstanceOf(WebPage::class, $webPage);
        $this->assertEquals($taskUrl, (string) $webPage->getUri());
        $this->assertEquals($webPageContent, $webPage->getContent());
    }
}
