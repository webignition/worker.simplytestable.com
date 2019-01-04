<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Functional\Services;

use App\Entity\CachedResource;
use App\Entity\Task\Task;
use App\Model\Source;
use App\Model\Task\Type;
use App\Model\Task\TypeInterface;
use App\Services\TaskCachedSourceWebPageRetriever;
use App\Services\TaskTypeService;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\ContentTypeFactory;
use App\Tests\Services\TestTaskFactory;
use Doctrine\ORM\EntityManagerInterface;
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

    /**
     * @dataProvider retrieveValidCachedResourceDataProvider
     */
    public function testRetrieveValidCachedResource(string $webPageContent, string $contentTypeString)
    {
        $testTaskFactory = self::$container->get(TestTaskFactory::class);
        $entityManager = self::$container->get(EntityManagerInterface::class);
        $contentTypeFactory = self::$container->get(ContentTypeFactory::class);

        $taskUrl = 'http://example.com';

        $task = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'url' => $taskUrl,
            'type' => TypeInterface::TYPE_HTML_VALIDATION,
        ]));

        $contentType = $contentTypeFactory->createContentType($contentTypeString);
        $testTaskFactory->addPrimaryCachedResourceSourceToTask($task, $webPageContent, $contentType);

        $primarySource = $task->getSources()[$taskUrl];
        $requestHash = $primarySource->getValue();

        /* @var CachedResource $cachedResource */
        /** @noinspection PhpUnhandledExceptionInspection */
        $cachedResource = $entityManager->find(CachedResource::class, $requestHash);

        $this->assertEquals($webPageContent, stream_get_contents($cachedResource->getBody()));

        $webPage = $this->taskCachedSourceWebPageRetriever->retrieve($task);

        $this->assertInstanceOf(WebPage::class, $webPage);
        $this->assertEquals($taskUrl, (string) $webPage->getUri());
        $this->assertEquals($webPageContent, $webPage->getContent());
        $this->assertEquals($contentType, $webPage->getContentType());
    }

    public function retrieveValidCachedResourceDataProvider(): array
    {
        return [
            'default text/html content type' => [
                'webPageContent' => 'web page content',
                'contentTypeString' => 'text/html',
            ],
            'text/html content type with character set attribute' => [
                'webPageContent' => 'web page content',
                'contentTypeString' => 'text/html; charset=utf-8',
            ],
        ];
    }
}
