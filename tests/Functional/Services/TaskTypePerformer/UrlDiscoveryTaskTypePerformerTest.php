<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\Task\TypeInterface;
use App\Services\CachedResourceFactory;
use App\Services\CachedResourceManager;
use App\Services\RequestIdentifierFactory;
use App\Services\SourceFactory;
use App\Services\TaskTypePerformer\TaskPerformerInterface;
use App\Tests\Services\TestTaskFactory;
use App\Services\TaskTypePerformer\UrlDiscoveryTaskTypePerformer;
use App\Tests\Factory\HtmlDocumentFactory;
use webignition\WebResource\WebPage\WebPage;

class UrlDiscoveryTaskTypePerformerTest extends AbstractWebPageTaskTypePerformerTest
{
    /**
     * @var UrlDiscoveryTaskTypePerformer
     */
    private $taskTypePerformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->taskTypePerformer = self::$container->get(UrlDiscoveryTaskTypePerformer::class);
    }

    protected function getTaskTypePerformer(): TaskPerformerInterface
    {
        return self::$container->get(UrlDiscoveryTaskTypePerformer::class);
    }

    protected function getTaskTypeString(): string
    {
        return TypeInterface::TYPE_URL_DISCOVERY;
    }

    /**
     * @dataProvider performSuccessDataProvider
     */
    public function testPerformSuccess(
        callable $taskCreator,
        callable $setUp,
        string $webPageContent,
        array $expectedDecodedOutput
    ) {
        /* @var Task $task */
        $task = $taskCreator($webPageContent);
        $setUp($task, $webPageContent);

        $this->setSuccessfulTaskPerformerWebPageRetrieverOnTaskPerformer(
            UrlDiscoveryTaskTypePerformer::class,
            $task,
            $webPageContent
        );

        $this->taskTypePerformer->perform($task);

        $this->assertEquals(Task::STATE_COMPLETED, $task->getState());

        $output = $task->getOutput();
        $this->assertInstanceOf(Output::class, $output);
        $this->assertEquals('application/json', $output->getContentType());
        $this->assertEquals(0, $output->getErrorCount());
        $this->assertEquals(0, $output->getWarningCount());

        $this->assertEquals(
            $expectedDecodedOutput,
            json_decode($output->getOutput(), true)
        );
    }

    public function performSuccessDataProvider(): array
    {
        return [
            'no urls, no sources' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);

                    return $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults([
                            'type' => $this->getTaskTypeString(),
                        ])
                    );
                },
                'setUp' => function (Task $task, string $content) {
                    $this->setSuccessfulTaskPerformerWebPageRetrieverOnTaskPerformer(
                        UrlDiscoveryTaskTypePerformer::class,
                        $task,
                        $content
                    );
                },
                'webPageContent' => HtmlDocumentFactory::load('minimal'),
                'expectedDecodedOutput' => [],
            ],
            'no urls, has source' => [
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
                'setUp' => function (Task $task, string $content) {
                    $this->setSuccessfulTaskPerformerWebPageRetrieverOnTaskPerformer(
                        UrlDiscoveryTaskTypePerformer::class,
                        $task,
                        $content
                    );
                },
                'webPageContent' => HtmlDocumentFactory::load('minimal'),
                'expectedDecodedOutput' => [],
            ],
            'no scope, no sources' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);

                    return $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults([
                            'type' => $this->getTaskTypeString(),
                        ])
                    );
                },
                'setUp' => function (Task $task, string $content) {
                    $this->setSuccessfulTaskPerformerWebPageRetrieverOnTaskPerformer(
                        UrlDiscoveryTaskTypePerformer::class,
                        $task,
                        $content
                    );
                },
                'webPageContent' => HtmlDocumentFactory::load('css-link-js-link-image-anchors'),
                'expectedDecodedOutput' => [
                    'http://example.com/foo/anchor1',
                    'http://www.example.com/foo/anchor2',
                    'http://bar.example.com/bar/anchor',
                    'https://www.example.com/foo/anchor1',
                ],
            ],
            'has scope, no sources' => [
                'taskCreator' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);

                    return $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults([
                            'type' => $this->getTaskTypeString(),
                            'parameters' => json_encode([
                                'scope' => [
                                    'http://example.com',
                                    'http://www.example.com',
                                ]
                            ]),
                        ])
                    );
                },
                'setUp' => function (Task $task, string $content) {
                    $this->setSuccessfulTaskPerformerWebPageRetrieverOnTaskPerformer(
                        UrlDiscoveryTaskTypePerformer::class,
                        $task,
                        $content
                    );
                },
                'webPageContent' => HtmlDocumentFactory::load('css-link-js-link-image-anchors'),
                'expectedDecodedOutput' => [
                    'http://example.com/foo/anchor1',
                    'http://www.example.com/foo/anchor2',
                    'https://www.example.com/foo/anchor1',
                ],
            ],
        ];
    }

    public function testHandles()
    {
        $this->assertFalse($this->taskTypePerformer->handles(TypeInterface::TYPE_HTML_VALIDATION));
        $this->assertFalse($this->taskTypePerformer->handles(TypeInterface::TYPE_CSS_VALIDATION));
        $this->assertFalse($this->taskTypePerformer->handles(TypeInterface::TYPE_LINK_INTEGRITY));
        $this->assertFalse($this->taskTypePerformer->handles(TypeInterface::TYPE_LINK_INTEGRITY_SINGLE_URL));
        $this->assertTrue($this->taskTypePerformer->handles(TypeInterface::TYPE_URL_DISCOVERY));
    }

    public function testGetPriority()
    {
        $this->assertEquals(
            self::$container->getParameter('url_discovery_task_type_performer_priority'),
            $this->taskTypePerformer->getPriority()
        );
    }
}
