<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Integration\Command\Task;

use App\Command\Task\PrepareCommand;
use App\Entity\CachedResource;
use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\Source;
use App\Model\Task\TypeInterface;
use App\Resque\Job\TaskReportCompletionJob;
use App\Tests\Factory\HtmlDocumentFactory;
use App\Tests\Services\HttpMockHandler;
use App\Tests\Services\TaskSourceContentsLoader;
use App\Tests\Services\TestTaskFactory;
use App\Services\Resque\QueueService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Console\Output\NullOutput;
use App\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use webignition\InternetMediaType\InternetMediaType;

/**
 * @group Command/Task/PrepareCommand
 */
class PrepareCommandTest extends AbstractBaseTestCase
{
    /**
     * @var PrepareCommand
     */
    private $command;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TaskSourceContentsLoader
     */
    private $taskSourceContentsLoader;

    protected function setUp()
    {
        parent::setUp();

        $this->command = self::$container->get(PrepareCommand::class);
        $this->entityManager = self::$container->get(EntityManagerInterface::class);
        $this->taskSourceContentsLoader = self::$container->get(TaskSourceContentsLoader::class);
    }

    /**
     * @dataProvider runTaskPreparedDataProvider
     */
    public function testRunTaskPrepared(
        array $httpFixtures,
        array $taskValues,
        array $expectedSources,
        array $expectedSourceContents
    ) {
        $testTaskFactory = self::$container->get(TestTaskFactory::class);
        $httpMockHandler = self::$container->get(HttpMockHandler::class);

        $httpMockHandler->appendFixtures($httpFixtures);

        $task = $testTaskFactory->create($taskValues);

        $this->assertEquals(Task::STATE_QUEUED, $task->getState());

        $returnCode = $this->command->run(
            new ArrayInput([
                'id' => $task->getId(),
            ]),
            new NullOutput()
        );

        $this->assertEquals(0, $returnCode);
        $this->assertEquals(Task::STATE_PREPARED, $task->getState());

        $sources = $task->getSources();
        $this->assertEquals($expectedSources, $sources);

        $sourceContents = $this->taskSourceContentsLoader->load($sources);
        $this->assertEquals($expectedSourceContents, $sourceContents);

        $this->assertEquals(Task::STATE_PREPARED, $task->getState());

        $this->assertTrue(self::$container->get(QueueService::class)->contains(
            'task-perform',
            [
                'id' => $task->getId()
            ]
        ));
    }

    public function runTaskPreparedDataProvider(): array
    {
        return [
            'html validation' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(200, ['content-type' => 'text/html'], '<doctype html>'),
                ],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_HTML_VALIDATION,
                ]),
                'expectedSources' => [
                    'http://example.com/' => new Source(
                        'http://example.com/',
                        Source::TYPE_CACHED_RESOURCE,
                        '4c2297fd8f408fa415ebfbc2d991f9ce'
                    ),
                ],
                'expectedSourceContents' => [
                    'http://example.com/' => '<doctype html>',
                ],
            ],
            'css validation, no stylesheets' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(200, ['content-type' => 'text/html'], '<doctype html>'),
                ],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_CSS_VALIDATION,
                ]),
                'expectedSources' => [
                    'http://example.com/' => new Source(
                        'http://example.com/',
                        Source::TYPE_CACHED_RESOURCE,
                        '4c2297fd8f408fa415ebfbc2d991f9ce'
                    ),
                ],
                'expectedSourceContents' => [
                    'http://example.com/' => '<doctype html>',
                ],
            ],
            'css validation, single linked stylesheet' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        HtmlDocumentFactory::load('empty-body-single-css-link')
                    ),
                    new Response(200, ['content-type' => 'text/css']),
                    new Response(200, ['content-type' => 'text/css'], 'html {}'),
                ],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_CSS_VALIDATION,
                ]),
                'expectedSources' => [
                    'http://example.com/' => new Source(
                        'http://example.com/',
                        Source::TYPE_CACHED_RESOURCE,
                        '4c2297fd8f408fa415ebfbc2d991f9ce'
                    ),
                    'http://example.com/style.css' => new Source(
                        'http://example.com/style.css',
                        Source::TYPE_CACHED_RESOURCE,
                        '10490a4daf45105812424ba6b4b77c36',
                        [
                            'origin' => 'resource',
                        ]
                    ),
                ],
                'expectedSourceContents' => [
                    'http://example.com/' => HtmlDocumentFactory::load('empty-body-single-css-link'),
                    'http://example.com/style.css' => 'html {}',
                ],
            ],
            'link integrity' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(200, ['content-type' => 'text/html'], '<doctype html>'),
                ],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_LINK_INTEGRITY,
                ]),
                'expectedSources' => [
                    'http://example.com/' => new Source(
                        'http://example.com/',
                        Source::TYPE_CACHED_RESOURCE,
                        '4c2297fd8f408fa415ebfbc2d991f9ce'
                    ),
                ],
                'expectedSourceContents' => [
                    'http://example.com/' => '<doctype html>',
                ],
            ],
            'url discovery' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(200, ['content-type' => 'text/html'], '<doctype html>'),
                ],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_URL_DISCOVERY,
                ]),
                'expectedSources' => [
                    'http://example.com/' => new Source(
                        'http://example.com/',
                        Source::TYPE_CACHED_RESOURCE,
                        '4c2297fd8f408fa415ebfbc2d991f9ce'
                    ),
                ],
                'expectedSourceContents' => [
                    'http://example.com/' => '<doctype html>',
                ],
            ],
        ];
    }

    /**
     * @dataProvider runTaskCompletedDataProvider
     */
    public function testRunTaskCompleted(
        array $httpFixtures,
        array $taskValues,
        bool $expectedHasPrimarySource,
        string $expectedTaskState,
        int $expectedErrorCount,
        $expectedDecodedOutput
    ) {
        $testTaskFactory = self::$container->get(TestTaskFactory::class);
        $httpMockHandler = self::$container->get(HttpMockHandler::class);
        $entityManager = self::$container->get(EntityManagerInterface::class);

        $httpMockHandler->appendFixtures($httpFixtures);

        $task = $testTaskFactory->create($taskValues);

        $sources = $task->getSources();
        $primarySource = $sources[$task->getUrl()];
        $primarySourceRequestHash = $primarySource->getValue();

        $primaryCachedResource = $entityManager->find(CachedResource::class, $primarySourceRequestHash);

        if ($expectedHasPrimarySource) {
            $this->assertNotNull($primaryCachedResource);
        } else {
            $this->assertNull($primaryCachedResource);
        }

        $returnCode = $this->command->run(
            new ArrayInput([
                'id' => $task->getId(),
            ]),
            new NullOutput()
        );

        $this->assertEquals(0, $returnCode);
        $this->assertEquals($expectedTaskState, $task->getState());

        $output = $task->getOutput();

        $this->assertInstanceOf(Output::class, $output);

        if ($output instanceof Output) {
            $this->assertEquals($expectedErrorCount, $output->getErrorCount());
            $this->assertEquals(0, $output->getWarningCount());
            $this->assertEquals('application/json', $output->getContentType());
            $this->assertEquals($expectedDecodedOutput, json_decode((string) $output->getContent(), true));
        }

        $this->assertTrue(self::$container->get(QueueService::class)->contains(
            TaskReportCompletionJob::QUEUE_NAME,
            [
                'id' => $task->getId()
            ]
        ));

        $this->assertNull($entityManager->find(CachedResource::class, $primarySourceRequestHash));
    }

    public function runTaskCompletedDataProvider(): array
    {
        return [
            'html validation, invalid character encoding' => [
                'httpFixtures' => [],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_HTML_VALIDATION,
                    'sources' => [
                        [
                            'type' => Source::TYPE_CACHED_RESOURCE,
                            'url' => 'http://example.com/',
                            'content' => "\xc3\x28",
                            'contentType' => new InternetMediaType('text', 'html'),
                        ],
                    ],
                ]),
                'expectedHasPrimarySource' => true,
                'expectedTaskState' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                'expectedErrorCount' => 1,
                'expectedDecodedOutput' => [
                    'messages' => [
                        [
                            'message' => 'utf-8',
                            'messageId' => 'invalid-character-encoding',
                            'type' => 'error',
                        ],
                    ],
                ],
            ],
            'html validation, empty primary source' => [
                'httpFixtures' => [],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_HTML_VALIDATION,
                    'sources' => [
                        [
                            'type' => Source::TYPE_CACHED_RESOURCE,
                            'url' => 'http://example.com/',
                            'content' => '',
                            'contentType' => new InternetMediaType('text', 'html'),
                        ],
                    ],
                ]),
                'expectedHasPrimarySource' => true,
                'expectedTaskState' => Task::STATE_SKIPPED,
                'expectedErrorCount' => 0,
                'expectedDecodedOutput' => null,
            ],
            'html validation, invalid content type primary source' => [
                'httpFixtures' => [],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_HTML_VALIDATION,
                    'sources' => [
                        [
                            'type' => Source::TYPE_INVALID,
                            'url' => 'http://example.com/',
                            'value' => 'invalid:' . sprintf(
                                Source::MESSAGE_INVALID_CONTENT_TYPE,
                                'text/html'
                            ),
                        ],
                    ],
                ]),
                'expectedHasPrimarySource' => false,
                'expectedTaskState' => Task::STATE_SKIPPED,
                'expectedErrorCount' => 0,
                'expectedDecodedOutput' => null,
            ],
            'html validation, failed primary source' => [
                'httpFixtures' => [],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_HTML_VALIDATION,
                    'sources' => [
                        [
                            'type' => Source::TYPE_UNAVAILABLE,
                            'url' => 'http://example.com/',
                            'value' => 'http:404',
                        ],
                    ],
                ]),
                'expectedHasPrimarySource' => false,
                'expectedTaskState' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                'expectedErrorCount' => 1,
                'expectedDecodedOutput' => [
                    'messages' => [
                        [
                            'message' => '',
                            'messageId' => 'http-retrieval-404',
                            'type' => 'error',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
