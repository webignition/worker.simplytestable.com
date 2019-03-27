<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Functional\Services;

use App\Entity\CachedResource;
use App\Entity\Task\Task;
use App\Model\Source;
use App\Model\Task\TypeInterface;
use App\Services\TaskCachedSourceWebPageRetriever;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\ContentTypeFactory;
use App\Tests\Services\ObjectReflector;
use App\Tests\Services\TaskTypeRetriever;
use App\Tests\Services\TestTaskFactory;
use Doctrine\ORM\EntityManagerInterface;
use webignition\InternetMediaType\InternetMediaType;
use webignition\InternetMediaType\Parser\ParseException;
use webignition\WebResource\WebPage\WebPage;
use webignition\InternetMediaType\Parser\Parser as ContentTypeParser;

class TaskCachedSourceWebPageRetrieverTest extends AbstractBaseTestCase
{
    const TASK_URL = 'http://example.com/';

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
        $source = new Source(self::TASK_URL, Source::TYPE_UNAVAILABLE, 'http:404');

        $task = $this->createTask();
        $task->addSource($source);

        $this->assertNull($this->taskCachedSourceWebPageRetriever->retrieve($task));
    }

    public function testRetrieveNoPrimaryResource()
    {
        $source = new Source('http://example.com/foo', Source::TYPE_CACHED_RESOURCE, 'request-hash');

        $task = $this->createTask();
        $task->addSource($source);

        $this->assertNull($this->taskCachedSourceWebPageRetriever->retrieve($task));
    }

    public function testRetrieveInvalidCachedResource()
    {
        $source = new Source(self::TASK_URL, Source::TYPE_CACHED_RESOURCE, 'invalid-request-hash');

        $task = $this->createTask();
        $task->addSource($source);

        $this->assertNull($this->taskCachedSourceWebPageRetriever->retrieve($task));
    }

    public function testRetrieveUnparseableCachedResourceContentType()
    {
        $testTaskFactory = self::$container->get(TestTaskFactory::class);
        $taskUrl = 'http://example.com';

        $task = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'url' => $taskUrl,
            'type' => TypeInterface::TYPE_HTML_VALIDATION,
        ]));


        $webPageContent = 'web page content';

        $testTaskFactory->addPrimaryCachedResourceSourceToTask(
            $task,
            $webPageContent,
            new InternetMediaType('text', 'html')
        );

        $contentTypeParser = \Mockery::mock(ContentTypeParser::class);
        $contentTypeParser
            ->shouldReceive('parse')
            ->with('text/html')
            ->andThrow(new ParseException());

        ObjectReflector::setProperty(
            $this->taskCachedSourceWebPageRetriever,
            TaskCachedSourceWebPageRetriever::class,
            'contentTypeParser',
            $contentTypeParser
        );

        $webPage = $this->taskCachedSourceWebPageRetriever->retrieve($task);

        $this->assertNull($webPage);
    }

    public function testRetrieveNonWebPageCachedResourceContentType()
    {
        $testTaskFactory = self::$container->get(TestTaskFactory::class);
        $taskUrl = 'http://example.com';

        $task = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'url' => $taskUrl,
            'type' => TypeInterface::TYPE_HTML_VALIDATION,
        ]));


        $webPageContent = 'web page content';

        $testTaskFactory->addPrimaryCachedResourceSourceToTask(
            $task,
            $webPageContent,
            new InternetMediaType('text', 'html')
        );

        $contentTypeParser = \Mockery::mock(ContentTypeParser::class);
        $contentTypeParser
            ->shouldReceive('parse')
            ->with('text/html')
            ->andReturn(new InternetMediaType('foo', 'bar'));

        ObjectReflector::setProperty(
            $this->taskCachedSourceWebPageRetriever,
            TaskCachedSourceWebPageRetriever::class,
            'contentTypeParser',
            $contentTypeParser
        );

        $webPage = $this->taskCachedSourceWebPageRetriever->retrieve($task);

        $this->assertNull($webPage);
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

        if ($cachedResource instanceof CachedResource) {
            $this->assertEquals($webPageContent, stream_get_contents($cachedResource->getBody()));
        }

        $webPage = $this->taskCachedSourceWebPageRetriever->retrieve($task);

        $this->assertInstanceOf(WebPage::class, $webPage);

        if ($webPage instanceof WebPage) {
            $this->assertEquals($taskUrl, (string) $webPage->getUri());
            $this->assertEquals($webPageContent, $webPage->getContent());
            $this->assertEquals($contentType, $webPage->getContentType());
        }
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

    private function createTask(): Task
    {
        $taskTypeRetriever = self::$container->get(TaskTypeRetriever::class);

        return Task::create(
            $taskTypeRetriever->retrieve(TypeInterface::TYPE_HTML_VALIDATION),
            self::TASK_URL,
            ''
        );
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }
}
