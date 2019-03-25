<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services\TaskTypePreparer;

use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Exception\UnableToRetrieveResourceException;
use App\Model\Source;
use App\Model\Task\Type;
use App\Model\Task\TypeInterface;
use App\Services\TaskSourceRetriever;
use App\Services\TaskTypePreparer\CssTaskSourcePreparer;
use App\Services\TaskTypeService;
use App\Tests\Factory\HtmlDocumentFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\HttpMockHandler;
use App\Tests\Services\ObjectReflector;
use App\Tests\Services\TestTaskFactory;
use GuzzleHttp\Psr7\Response;
use webignition\InternetMediaType\InternetMediaType;
use webignition\WebResource\Retriever as WebResourceRetriever;

class CssTaskSourcePreparerTest extends AbstractBaseTestCase
{
    /**
     * @var CssTaskSourcePreparer
     */
    private $preparer;

    /**
     * @var TestTaskFactory
     */
    private $testTaskFactory;

    /**
     * @var WebResourceRetriever
     */
    private $cssWebResourceRetriever;

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
        $this->testTaskFactory = self::$container->get(TestTaskFactory::class);

        $this->cssWebResourceRetriever = self::$container->get('app.services.web-resource-retriever.css');
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
    }

    /**
     * @dataProvider wrongTaskTypeDataProvider
     */
    public function testPrepareWrongTaskType(string $taskType)
    {
        $task = Task::create($this->getTaskType($taskType), 'http://example.com/');

        $this->assertNull($this->preparer->prepare($task));
    }

    /**
     * @dataProvider wrongTaskTypeDataProvider
     */
    public function testInvokeWrongTaskType(string $taskType)
    {
        $task = Task::create($this->getTaskType($taskType), 'http://example.com/');

        $taskEvent = new TaskEvent($task);

        $this->preparer->__invoke(new TaskEvent($task));
        $this->assertFalse($taskEvent->isPropagationStopped());
    }

    public function wrongTaskTypeDataProvider(): array
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

    /**
     * @dataProvider prepareSuccessDataProvider
     */
    public function testPrepareSuccess(
        array $taskValues,
        array $httpFixtures,
        bool $expectedPreparationIsComplete,
        array $expectedSources
    ) {
        $task = $this->testTaskFactory->create($taskValues);
        $this->httpMockHandler->appendFixtures($httpFixtures);

        $preparationIsComplete = $this->preparer->prepare($task);

        $this->assertEquals($expectedPreparationIsComplete, $preparationIsComplete);
        $this->assertEquals($expectedSources, $task->getSources());
    }

    /**
     * @dataProvider prepareSuccessDataProvider
     */
    public function testInvokeSuccess(
        array $taskValues,
        array $httpFixtures,
        bool $expectedPreparationIsComplete,
        array $expectedSources
    ) {
        $task = $this->testTaskFactory->create($taskValues);
        $this->httpMockHandler->appendFixtures($httpFixtures);

        $taskEvent = new TaskEvent($task);

        $this->preparer->__invoke($taskEvent);

        $this->assertEquals(!$expectedPreparationIsComplete, $taskEvent->isPropagationStopped());
        $this->assertEquals($expectedSources, $task->getSources());
    }

    public function prepareSuccessDataProvider(): array
    {
        return [
            'no stylesheet urls' => [
                'taskValues' => [
                    'url' => 'http://example.com',
                    'type' =>  Type::TYPE_CSS_VALIDATION,
                    'parameters' => '',
                    'state' => Task::STATE_PREPARING,
                    'sources' => [
                        [
                            'url' => 'http://example.com',
                            'content' => '<!doctype html>',
                            'contentType' => new InternetMediaType('text', 'html'),
                        ],
                    ],
                ],
                'httpFixtures' => [],
                'expectedPreparationIsComplete' => true,
                'expectedSources' => [
                    'http://example.com' => new Source(
                        'http://example.com',
                        Source::TYPE_CACHED_RESOURCE,
                        '0d633f5a406af4dc8ebcc4201087bce6'
                    ),
                ],
            ],
            'single stylesheet url' => [
                'taskValues' => [
                    'url' => 'http://example.com',
                    'type' =>  Type::TYPE_CSS_VALIDATION,
                    'parameters' => '',
                    'state' => Task::STATE_PREPARING,
                    'sources' => [
                        [
                            'url' => 'http://example.com',
                            'content' => HtmlDocumentFactory::load('empty-body-single-css-link'),
                            'contentType' => new InternetMediaType('text', 'html'),
                        ],
                    ],
                ],
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/css']),
                    new Response(200, ['content-type' => 'text/css']),
                ],
                'expectedPreparationIsComplete' => true,
                'expectedSources' => [
                    'http://example.com' => new Source(
                        'http://example.com',
                        Source::TYPE_CACHED_RESOURCE,
                        '0d633f5a406af4dc8ebcc4201087bce6'
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
            ],
            'two stylesheet urls, none sourced' => [
                'taskValues' => [
                    'url' => 'http://example.com',
                    'type' =>  Type::TYPE_CSS_VALIDATION,
                    'parameters' => '',
                    'state' => Task::STATE_PREPARING,
                    'sources' => [
                        [
                            'url' => 'http://example.com',
                            'content' => HtmlDocumentFactory::load('empty-body-two-css-links'),
                            'contentType' => new InternetMediaType('text', 'html'),
                        ],
                    ],
                ],
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/css']),
                    new Response(200, ['content-type' => 'text/css']),
                ],
                'expectedPreparationIsComplete' => false,
                'expectedSources' => [
                    'http://example.com' => new Source(
                        'http://example.com',
                        Source::TYPE_CACHED_RESOURCE,
                        '0d633f5a406af4dc8ebcc4201087bce6'
                    ),
                    'http://example.com/one.css' => new Source(
                        'http://example.com/one.css',
                        Source::TYPE_CACHED_RESOURCE,
                        '7a39b475cf06e8626219dd25314c0e20',
                        [
                            'origin' => 'resource',
                        ]
                    ),
                ],
            ],
            'two stylesheet urls, first sourced' => [
                'taskValues' => [
                    'url' => 'http://example.com',
                    'type' =>  Type::TYPE_CSS_VALIDATION,
                    'parameters' => '',
                    'state' => Task::STATE_PREPARING,
                    'sources' => [
                        [
                            'url' => 'http://example.com',
                            'content' => HtmlDocumentFactory::load('empty-body-two-css-links'),
                            'contentType' => new InternetMediaType('text', 'html'),
                        ],
                        [
                            'url' => 'http://example.com/one.css',
                            'content' => 'html {}',
                            'contentType' => new InternetMediaType('text', 'css'),
                            'context' => [
                                'origin' => 'resource',
                            ],
                        ],
                    ],
                ],
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/css']),
                    new Response(200, ['content-type' => 'text/css']),
                ],
                'expectedPreparationIsComplete' => true,
                'expectedSources' => [
                    'http://example.com' => new Source(
                        'http://example.com',
                        Source::TYPE_CACHED_RESOURCE,
                        '0d633f5a406af4dc8ebcc4201087bce6'
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
                            'origin' => 'resource',
                        ]
                    ),
                ],
            ],
            'single linked stylesheet, single import, none sourced' => [
                'taskValues' => [
                    'url' => 'http://example.com',
                    'type' =>  Type::TYPE_CSS_VALIDATION,
                    'parameters' => '',
                    'state' => Task::STATE_PREPARING,
                    'sources' => [
                        [
                            'url' => 'http://example.com',
                            'content' => HtmlDocumentFactory::load('single-linked-stylesheet-single-import'),
                            'contentType' => new InternetMediaType('text', 'html'),
                        ],
                    ],
                ],
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/css']),
                    new Response(200, ['content-type' => 'text/css']),
                ],
                'expectedPreparationIsComplete' => false,
                'expectedSources' => [
                    'http://example.com' => new Source(
                        'http://example.com',
                        Source::TYPE_CACHED_RESOURCE,
                        '0d633f5a406af4dc8ebcc4201087bce6'
                    ),
                    'http://example.com/one.css' => new Source(
                        'http://example.com/one.css',
                        Source::TYPE_CACHED_RESOURCE,
                        '7a39b475cf06e8626219dd25314c0e20',
                        [
                            'origin' => 'resource',
                        ]
                    ),
                ],
            ],
            'single linked stylesheet, single import, linked stylesheet sourced' => [
                'taskValues' => [
                    'url' => 'http://example.com',
                    'type' =>  Type::TYPE_CSS_VALIDATION,
                    'parameters' => '',
                    'state' => Task::STATE_PREPARING,
                    'sources' => [
                        [
                            'url' => 'http://example.com',
                            'content' => HtmlDocumentFactory::load('single-linked-stylesheet-single-import'),
                            'contentType' => new InternetMediaType('text', 'html'),
                        ],
                        [
                            'url' => 'http://example.com/one.css',
                            'content' => 'html {}',
                            'contentType' => new InternetMediaType('text', 'css'),
                            'context' => [
                                'origin' => 'resource',
                            ],
                        ],
                    ],
                ],
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/css']),
                    new Response(200, ['content-type' => 'text/css']),
                ],
                'expectedPreparationIsComplete' => true,
                'expectedSources' => [
                    'http://example.com' => new Source(
                        'http://example.com',
                        Source::TYPE_CACHED_RESOURCE,
                        '0d633f5a406af4dc8ebcc4201087bce6'
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
            ],
            'single linked stylesheet, single import, linked stylesheet sourced, import in linked stylesheet' => [
                'taskValues' => [
                    'url' => 'http://example.com',
                    'type' =>  Type::TYPE_CSS_VALIDATION,
                    'parameters' => '',
                    'state' => Task::STATE_PREPARING,
                    'sources' => [
                        [
                            'url' => 'http://example.com',
                            'content' => HtmlDocumentFactory::load('single-linked-stylesheet-single-import'),
                            'contentType' => new InternetMediaType('text', 'html'),
                        ],
                        [
                            'url' => 'http://example.com/one.css',
                            'content' => '@import "three.css";',
                            'contentType' => new InternetMediaType('text', 'css'),
                            'context' => [
                                'origin' => 'resource',
                            ],
                        ],
                    ],
                ],
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/css']),
                    new Response(200, ['content-type' => 'text/css']),
                ],
                'expectedPreparationIsComplete' => false,
                'expectedSources' => [
                    'http://example.com' => new Source(
                        'http://example.com',
                        Source::TYPE_CACHED_RESOURCE,
                        '0d633f5a406af4dc8ebcc4201087bce6'
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
            ],
            'single linked stylesheet, single import, import in linked stylesheet, all but linked import sourced' => [
                'taskValues' => [
                    'url' => 'http://example.com',
                    'type' =>  Type::TYPE_CSS_VALIDATION,
                    'parameters' => '',
                    'state' => Task::STATE_PREPARING,
                    'sources' => [
                        [
                            'url' => 'http://example.com',
                            'content' => HtmlDocumentFactory::load('single-linked-stylesheet-single-import'),
                            'contentType' => new InternetMediaType('text', 'html'),
                        ],
                        [
                            'url' => 'http://example.com/one.css',
                            'content' => '@import "three.css";',
                            'contentType' => new InternetMediaType('text', 'css'),
                            'context' => [
                                'origin' => 'resource',
                            ],
                        ],
                        [
                            'url' => 'http://example.com/two.css',
                            'content' => 'body {}',
                            'contentType' => new InternetMediaType('text', 'css'),
                            'context' => [
                                'origin' => 'import',
                            ],
                        ],
                    ],
                ],
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/css']),
                    new Response(200, ['content-type' => 'text/css']),
                ],
                'expectedPreparationIsComplete' => true,
                'expectedSources' => [
                    'http://example.com' => new Source(
                        'http://example.com',
                        Source::TYPE_CACHED_RESOURCE,
                        '0d633f5a406af4dc8ebcc4201087bce6'
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
                    'http://example.com/three.css' => new Source(
                        'http://example.com/three.css',
                        Source::TYPE_CACHED_RESOURCE,
                        'e885bdfdaab2711aa9424dd7f29fd3c7',
                        [
                            'origin' => 'import',
                        ]
                    ),
                ],
            ],
            'single linked stylesheet, single import, import in linked stylesheet, all sourced' => [
                'taskValues' => [
                    'url' => 'http://example.com',
                    'type' =>  Type::TYPE_CSS_VALIDATION,
                    'parameters' => '',
                    'state' => Task::STATE_PREPARING,
                    'sources' => [
                        [
                            'url' => 'http://example.com',
                            'content' => HtmlDocumentFactory::load('single-linked-stylesheet-single-import'),
                            'contentType' => new InternetMediaType('text', 'html'),
                        ],
                        [
                            'url' => 'http://example.com/one.css',
                            'content' => '@import "three.css";',
                            'contentType' => new InternetMediaType('text', 'css'),
                            'context' => [
                                'origin' => 'resource',
                            ],
                        ],
                        [
                            'url' => 'http://example.com/two.css',
                            'content' => 'body {}',
                            'contentType' => new InternetMediaType('text', 'css'),
                            'context' => [
                                'origin' => 'import',
                            ],
                        ],
                        [
                            'url' => 'http://example.com/three.css',
                            'content' => 'html {}',
                            'contentType' => new InternetMediaType('text', 'css'),
                            'context' => [
                                'origin' => 'import',
                            ],
                        ],
                    ],
                ],
                'httpFixtures' => [],
                'expectedPreparationIsComplete' => true,
                'expectedSources' => [
                    'http://example.com' => new Source(
                        'http://example.com',
                        Source::TYPE_CACHED_RESOURCE,
                        '0d633f5a406af4dc8ebcc4201087bce6'
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
                    'http://example.com/three.css' => new Source(
                        'http://example.com/three.css',
                        Source::TYPE_CACHED_RESOURCE,
                        'e885bdfdaab2711aa9424dd7f29fd3c7',
                        [
                            'origin' => 'import',
                        ]
                    ),
                ],
            ],
        ];
    }

    public function testPrepareCannotAcquireLock()
    {
        $taskSourceRetriever = \Mockery::mock(TaskSourceRetriever::class);
        $taskSourceRetriever
            ->shouldReceive('retrieve')
            ->andReturn(false);

        ObjectReflector::setProperty(
            $this->preparer,
            CssTaskSourcePreparer::class,
            'taskSourceRetriever',
            $taskSourceRetriever
        );

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => TypeInterface::TYPE_CSS_VALIDATION,
            'sources' => [
                [
                    'url' => 'http://example.com/',
                    'content' => HtmlDocumentFactory::load('empty-body-single-css-link'),
                    'contentType' => new InternetMediaType('text', 'html'),
                ],
            ],
        ]));

        $this->expectException(UnableToRetrieveResourceException::class);

        $this->preparer->prepare($task);
    }

    protected function tearDown()
    {
        parent::tearDown();

        \Mockery::close();
    }

    private function getTaskType(string $name): Type
    {
        $type = self::$container->get(TaskTypeService::class)->get($name);

        if (!$type instanceof Type) {
            throw new \RuntimeException();
        }

        return $type;
    }
}
