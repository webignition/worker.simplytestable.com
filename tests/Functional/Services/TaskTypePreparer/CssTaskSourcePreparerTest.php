<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services\TaskTypePreparer;

use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Model\Task\Type;
use App\Services\TaskTypePreparer\CssTaskSourcePreparer;
use App\Services\TaskTypeService;
use App\Tests\Factory\HtmlDocumentFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\HttpMockHandler;
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
        $taskTypeService = self::$container->get(TaskTypeService::class);
        $task = Task::create($taskTypeService->get($taskType), 'http://example.com/');

        $this->assertNull($this->preparer->prepare($task));
    }

    /**
     * @dataProvider wrongTaskTypeDataProvider
     */
    public function testInvokeWrongTaskType(string $taskType)
    {
        $taskTypeService = self::$container->get(TaskTypeService::class);
        $task = Task::create($taskTypeService->get($taskType), 'http://example.com/');

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
        array $expectedSourceUrls
    ) {
        $task = $this->testTaskFactory->create($taskValues);
        $this->httpMockHandler->appendFixtures($httpFixtures);

        $preparationIsComplete = $this->preparer->prepare($task);

        $this->assertEquals($expectedPreparationIsComplete, $preparationIsComplete);
        $this->assertEquals($expectedSourceUrls, array_keys($task->getSources()));
    }

    /**
     * @dataProvider prepareSuccessDataProvider
     */
    public function testInvokeSuccess(
        array $taskValues,
        array $httpFixtures,
        bool $expectedPreparationIsComplete,
        array $expectedSourceUrls
    ) {
        $task = $this->testTaskFactory->create($taskValues);
        $this->httpMockHandler->appendFixtures($httpFixtures);

        $taskEvent = new TaskEvent($task);

        $this->preparer->__invoke($taskEvent);

        $this->assertEquals(!$expectedPreparationIsComplete, $taskEvent->isPropagationStopped());
        $this->assertEquals($expectedSourceUrls, array_keys($task->getSources()));
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
                'expectedSourceUrls' => [
                    'http://example.com',
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
                'expectedSourceUrls' => [
                    'http://example.com',
                    'http://example.com/style.css',
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
                'expectedSourceUrls' => [
                    'http://example.com',
                    'http://example.com/one.css',
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
                        ],
                    ],
                ],
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/css']),
                    new Response(200, ['content-type' => 'text/css']),
                ],
                'expectedPreparationIsComplete' => true,
                'expectedSourceUrls' => [
                    'http://example.com',
                    'http://example.com/one.css',
                    'http://example.com/two.css',
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
                'expectedSourceUrls' => [
                    'http://example.com',
                    'http://example.com/one.css',
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
                        ],
                    ],
                ],
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/css']),
                    new Response(200, ['content-type' => 'text/css']),
                ],
                'expectedPreparationIsComplete' => true,
                'expectedSourceUrls' => [
                    'http://example.com',
                    'http://example.com/one.css',
                    'http://example.com/two.css',
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
                        ],
                    ],
                ],
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/css']),
                    new Response(200, ['content-type' => 'text/css']),
                ],
                'expectedPreparationIsComplete' => false,
                'expectedSourceUrls' => [
                    'http://example.com',
                    'http://example.com/one.css',
                    'http://example.com/two.css',
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
                        ],
                        [
                            'url' => 'http://example.com/two.css',
                            'content' => 'body {}',
                            'contentType' => new InternetMediaType('text', 'css'),
                        ],
                    ],
                ],
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/css']),
                    new Response(200, ['content-type' => 'text/css']),
                ],
                'expectedPreparationIsComplete' => true,
                'expectedSourceUrls' => [
                    'http://example.com',
                    'http://example.com/one.css',
                    'http://example.com/two.css',
                    'http://example.com/three.css',
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
                        ],
                        [
                            'url' => 'http://example.com/two.css',
                            'content' => 'body {}',
                            'contentType' => new InternetMediaType('text', 'css'),
                        ],
                        [
                            'url' => 'http://example.com/three.css',
                            'content' => 'html {}',
                            'contentType' => new InternetMediaType('text', 'css'),
                        ],
                    ],
                ],
                'httpFixtures' => [],
                'expectedPreparationIsComplete' => true,
                'expectedSourceUrls' => [
                    'http://example.com',
                    'http://example.com/one.css',
                    'http://example.com/two.css',
                    'http://example.com/three.css',
                ],
            ],
        ];
    }
}
