<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Integration\Services;

use App\Entity\Task\Task;
use App\Model\Source;
use App\Model\Task\TypeInterface;
use App\Services\Resque\QueueService;
use App\Services\TaskPreparer;
use App\Tests\Factory\HtmlDocumentFactory;
use App\Tests\Services\HttpMockHandler;
use App\Tests\Services\TaskSourceContentsLoader;
use App\Tests\Services\TestTaskFactory;
use Doctrine\ORM\EntityManagerInterface;
use App\Tests\Functional\AbstractBaseTestCase;
use GuzzleHttp\Psr7\Response;

class TaskPreparerTest extends AbstractBaseTestCase
{
    /**
     * @var TaskPreparer
     */
    private $taskPreparer;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var HttpMockHandler
     */
    private $httpMockHandler;

    /**
     * @var TaskSourceContentsLoader
     */
    private $taskSourceContentsLoader;

    protected function setUp()
    {
        parent::setUp();

        $this->taskPreparer = self::$container->get(TaskPreparer::class);
        $this->entityManager = self::$container->get(EntityManagerInterface::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
        $this->taskSourceContentsLoader = self::$container->get(TaskSourceContentsLoader::class);
    }

    /**
     * @dataProvider prepareDataProvider
     */
    public function testPrepare(
        array $httpFixtures,
        array $taskValues,
        int $prepareRunCount,
        array $expectedSources,
        array $expectedSourceContents
    ) {
        $testTaskFactory = self::$container->get(TestTaskFactory::class);

        $this->httpMockHandler->appendFixtures($httpFixtures);

        $task = $testTaskFactory->create($taskValues);
        $this->assertEquals(Task::STATE_QUEUED, $task->getState());

        for ($runCount = 0; $runCount < $prepareRunCount; $runCount++) {
            $this->taskPreparer->prepare($task);
        }

        $this->assertEquals(Task::STATE_PREPARED, $task->getState());

        $sources = $task->getSources();
        $this->assertEquals($expectedSources, $sources);

        $sourceContents = $this->taskSourceContentsLoader->load($sources);
        $this->assertEquals($expectedSourceContents, $sourceContents);

        $this->assertTrue(self::$container->get(QueueService::class)->contains(
            'task-perform',
            [
                'id' => $task->getId()
            ]
        ));
    }

    public function prepareDataProvider(): array
    {
        return [
            'html validation, single execution' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(200, ['content-type' => 'text/html'], '<doctype html>'),
                ],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_HTML_VALIDATION,
                ]),
                'prepareRunCount' => 1,
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
            'html validation, double execution' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(200, ['content-type' => 'text/html'], '<doctype html>'),
                ],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_HTML_VALIDATION,
                ]),
                'prepareRunCount' => 2,
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
                'prepareRunCount' => 1,
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
            'css validation, single linked stylesheet, single import' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        HtmlDocumentFactory::load('single-linked-stylesheet-single-import')
                    ),
                    new Response(200, ['content-type' => 'text/css']),
                    new Response(200, ['content-type' => 'text/css'], '.linked {}'),
                    new Response(200, ['content-type' => 'text/css']),
                    new Response(200, ['content-type' => 'text/css'], '.import {}'),
                ],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_CSS_VALIDATION,
                ]),
                'prepareRunCount' => 2,
                'expectedSources' => [
                    'http://example.com/' => new Source(
                        'http://example.com/',
                        Source::TYPE_CACHED_RESOURCE,
                        '4c2297fd8f408fa415ebfbc2d991f9ce'
                    ),
                    'http://example.com/one.css' => new Source(
                        'http://example.com/one.css',
                        Source::TYPE_CACHED_RESOURCE,
                        '7a39b475cf06e8626219dd25314c0e20',
                        [
                            'origin' => 'resource',
                        ]
                    ),
                    'http://example.com/two.css' => new Source(
                        'http://example.com/two.css',
                        Source::TYPE_CACHED_RESOURCE,
                        '71ccc1362462e64378b12fb9f1c30c02',
                        [
                            'origin' => 'import',
                        ]
                    ),
                ],
                'expectedSourceContents' => [
                    'http://example.com/' => HtmlDocumentFactory::load('single-linked-stylesheet-single-import'),
                    'http://example.com/one.css' => '.linked {}',
                    'http://example.com/two.css' => '.import {}',
                ],
            ],
            'css validation, single linked stylesheet, single import, additional import in inmport' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        HtmlDocumentFactory::load('single-linked-stylesheet-single-import')
                    ),
                    new Response(200, ['content-type' => 'text/css']),
                    new Response(200, ['content-type' => 'text/css'], '.linked {}'),
                    new Response(200, ['content-type' => 'text/css']),
                    new Response(200, ['content-type' => 'text/css'], '@import "import-import.css";'),
                    new Response(200, ['content-type' => 'text/css']),
                    new Response(200, ['content-type' => 'text/css'], '.import-import {}'),
                ],
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'url' => 'http://example.com/',
                    'type' => TypeInterface::TYPE_CSS_VALIDATION,
                ]),
                'prepareRunCount' => 3,
                'expectedSources' => [
                    'http://example.com/' => new Source(
                        'http://example.com/',
                        Source::TYPE_CACHED_RESOURCE,
                        '4c2297fd8f408fa415ebfbc2d991f9ce'
                    ),
                    'http://example.com/one.css' => new Source(
                        'http://example.com/one.css',
                        Source::TYPE_CACHED_RESOURCE,
                        '7a39b475cf06e8626219dd25314c0e20',
                        [
                            'origin' => 'resource',
                        ]
                    ),
                    'http://example.com/two.css' => new Source(
                        'http://example.com/two.css',
                        Source::TYPE_CACHED_RESOURCE,
                        '71ccc1362462e64378b12fb9f1c30c02',
                        [
                            'origin' => 'import',
                        ]
                    ),
                    'http://example.com/import-import.css' => new Source(
                        'http://example.com/import-import.css',
                        Source::TYPE_CACHED_RESOURCE,
                        '5a1b452d6bb96c17137bbde519bd3881',
                        [
                            'origin' => 'import',
                        ]
                    ),
                ],
                'expectedSourceContents' => [
                    'http://example.com/' => HtmlDocumentFactory::load('single-linked-stylesheet-single-import'),
                    'http://example.com/one.css' => '.linked {}',
                    'http://example.com/two.css' => '@import "import-import.css";',
                    'http://example.com/import-import.css' => '.import-import {}',
                ],
            ],
        ];
    }

    protected function assertPostConditions()
    {
        parent::assertPostConditions();

        $this->assertEmpty($this->httpMockHandler->count());
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
