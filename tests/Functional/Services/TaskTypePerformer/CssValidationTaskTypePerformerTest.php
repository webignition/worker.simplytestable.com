<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\Source;
use App\Model\Task\TypeInterface;
use App\Tests\Services\ObjectReflector;
use App\Tests\Services\TestTaskFactory;
use App\Services\TaskTypePerformer\CssValidationTaskTypePerformer;
use App\Tests\Factory\CssValidatorFixtureFactory;
use App\Tests\Factory\HtmlDocumentFactory;
use webignition\CssValidatorWrapper\SourceStorage;
use webignition\CssValidatorWrapper\VendorExtensionSeverityLevel;
use webignition\CssValidatorWrapper\Wrapper as CssValidatorWrapper;
use webignition\InternetMediaType\InternetMediaType;
use webignition\UrlSourceMap\Source as SourceMapSource;
use webignition\UrlSourceMap\SourceMap;

class CssValidationTaskTypePerformerTest extends AbstractWebPageTaskTypePerformerTest
{
    /**
     * @var CssValidationTaskTypePerformer
     */
    private $taskTypePerformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->taskTypePerformer = self::$container->get(CssValidationTaskTypePerformer::class);
    }

    public function testPerformAlreadyHasOutput()
    {
        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => TypeInterface::TYPE_CSS_VALIDATION,
        ]));

        $output = Output::create('', new InternetMediaType('application', 'json'));
        $task->setOutput($output);
        $this->assertSame($output, $task->getOutput());

        $taskState = $task->getState();

        $this->taskTypePerformer->perform($task);

        $this->assertEquals($taskState, $task->getState());
        $this->assertSame($output, $task->getOutput());
    }

    /**
     * @dataProvider performSuccessDataProvider
     */
    public function testPerformSuccess(
        array $taskValues,
        SourceMap $wrapperLocalSourceMap,
        string $cssValidatorOutput,
        string $expectedTaskState,
        int $expectedErrorCount,
        int $expectedWarningCount,
        array $expectedDecodedOutput
    ) {
        $sourceStorage = self::$container->get(SourceStorage::class);

        $mockedSourceStorage = \Mockery::mock($sourceStorage);
        $mockedSourceStorage
            ->shouldReceive('storeCssResources')
            ->andReturn($wrapperLocalSourceMap);

        $mockedSourceStorage
            ->shouldReceive('storeWebPage')
            ->andReturn($wrapperLocalSourceMap);

        $cssValidatorWrapper = self::$container->get(CssValidatorWrapper::class);

        ObjectReflector::setProperty(
            $cssValidatorWrapper,
            CssValidatorWrapper::class,
            'sourceStorage',
            $mockedSourceStorage
        );

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults($taskValues));


        CssValidatorFixtureFactory::set($cssValidatorOutput);

        $this->taskTypePerformer->perform($task);

        $this->assertEquals($expectedTaskState, $task->getState());

        $output = $task->getOutput();
        $this->assertInstanceOf(Output::class, $output);

        if ($output instanceof Output) {
            $this->assertEquals('application/json', $output->getContentType());
            $this->assertEquals($expectedErrorCount, $output->getErrorCount());
            $this->assertEquals($expectedWarningCount, $output->getWarningCount());

            $this->assertEquals(
                $expectedDecodedOutput,
                json_decode((string) $output->getContent(), true)
            );
        }
    }

    public function performSuccessDataProvider(): array
    {
        return [
            'unknown validator exception' => [
                'taskValues' => [
                    'url' => 'http://example.com/',
                    'sources' => [
                        [
                            'type' => Source::TYPE_CACHED_RESOURCE,
                            'url' => 'http://example.com/',
                            'content' => '<!doctype html>',
                            'contentType' => new InternetMediaType('text', 'html'),
                        ],
                    ],
                ],
                'wrapperLocalSourceMap' => new SourceMap([
                    new SourceMapSource('http://example.com/', 'file:/tmp/web-page-hash.html'),
                ]),
                'cssValidatorOutput' => CssValidatorFixtureFactory::load('unknown-exception'),
                'expectedTaskState' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    [
                        'message' => 'Unknown error',
                        'class' => 'css-validation-exception-unknown',
                        'type' => 'error',
                        'context' => '',
                        'ref' => 'http://example.com/',
                        'line_number' => 0,
                    ],
                ],
            ],
            'no errors, ignore warnings' => [
                'taskValues' => [
                    'url' => 'http://example.com/',
                    'parameters' => json_encode([
                        'ignore-warnings' => true,
                    ]),
                    'sources' => [
                        [
                            'type' => Source::TYPE_CACHED_RESOURCE,
                            'url' => 'http://example.com/',
                            'content' => '<!doctype html>',
                            'contentType' => new InternetMediaType('text', 'html'),
                        ],
                    ],
                ],
                'wrapperLocalSourceMap' => new SourceMap([
                    new SourceMapSource('http://example.com/', 'file:/tmp/web-page-hash.html'),
                ]),
                'cssValidatorOutput' => CssValidatorFixtureFactory::load('1-vendor-extension-warning', [
                    '{{ webPageMappedUri }}' => 'file:/tmp/web-page-hash.html',
                ]),
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
            'no errors, ignore vendor extension warnings' => [
                'taskValues' => [
                    'url' => 'http://example.com/',
                    'parameters' => json_encode([
                        'vendor-extensions' => VendorExtensionSeverityLevel::LEVEL_IGNORE,
                    ]),
                    'sources' => [
                        [
                            'type' => Source::TYPE_CACHED_RESOURCE,
                            'url' => 'http://example.com/',
                            'content' => '<!doctype html>',
                            'contentType' => new InternetMediaType('text', 'html'),
                        ],
                    ],
                ],
                'wrapperLocalSourceMap' => new SourceMap([
                    new SourceMapSource('http://example.com/', 'file:/tmp/web-page-hash.html'),
                ]),
                'cssValidatorOutput' => CssValidatorFixtureFactory::load('1-vendor-extension-warning', [
                    '{{ webPageMappedUri }}' => 'file:/tmp/web-page-hash.html',
                ]),
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
            'three errors' => [
                'taskValues' => [
                    'url' => 'http://example.com/',
                    'parameters' => json_encode([
                        'ignore-warnings' => true,
                    ]),
                    'sources' => [
                        [
                            'type' => Source::TYPE_CACHED_RESOURCE,
                            'url' => 'http://example.com/',
                            'content' => '<!doctype html>',
                            'contentType' => new InternetMediaType('text', 'html'),
                        ],
                    ],
                ],
                'wrapperLocalSourceMap' => new SourceMap([
                    new SourceMapSource('http://example.com/', 'file:/tmp/web-page-hash.html'),
                ]),
                'cssValidatorOutput' => CssValidatorFixtureFactory::load('3-errors', [
                    '{{ webPageMappedUri }}' => 'file:/tmp/web-page-hash.html',
                ]),
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 3,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    [
                        'message' => 'one',
                        'context' => 'audio, canvas, video',
                        'line_number' => 1,
                        'type' => 'error',
                        'ref' => 'http://example.com/',
                    ],
                    [
                        'message' => 'two',
                        'context' => 'html',
                        'line_number' => 2,
                        'type' => 'error',
                        'ref' => 'http://example.com/',
                    ],
                    [
                        'message' => 'three',
                        'context' => '.hide-text',
                        'line_number' => 3,
                        'type' => 'error',
                        'ref' => 'http://example.com/',
                    ],
                ],
            ],
            'http 404 on linked resource' => [
                'taskValues' => [
                    'url' => 'http://example.com/',
                    'parameters' => json_encode([
                        'ignore-warnings' => true,
                    ]),
                    'sources' => [
                        [
                            'type' => Source::TYPE_CACHED_RESOURCE,
                            'url' => 'http://example.com/',
                            'content' => HtmlDocumentFactory::load('empty-body-single-css-link'),
                            'contentType' => new InternetMediaType('text', 'html'),
                        ],
                        [
                            'url' => 'http://example.com/style.css',
                            'type' => Source::TYPE_UNAVAILABLE,
                            'value' => 'http:404',
                        ],
                    ],
                ],
                'wrapperLocalSourceMap' => new SourceMap([
                    new SourceMapSource('http://example.com/', 'file:/tmp/web-page-hash.html'),
                ]),
                'cssValidatorOutput' => CssValidatorFixtureFactory::load('no-messages', [
                    '{{ webPageMappedUri }}' => 'file:/tmp/web-page-hash.html',
                ]),
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    [
                        'message' => 'http-retrieval-404',
                        'type' => 'error',
                        'context' => '',
                        'ref' => 'http://example.com/style.css',
                        'line_number' => 0,
                    ],
                ],
            ],
            'http 500 on linked resource' => [
                'taskValues' => [
                    'url' => 'http://example.com/',
                    'parameters' => json_encode([
                        'ignore-warnings' => true,
                    ]),
                    'sources' => [
                        [
                            'type' => Source::TYPE_CACHED_RESOURCE,
                            'url' => 'http://example.com/',
                            'content' => HtmlDocumentFactory::load('empty-body-single-css-link'),
                            'contentType' => new InternetMediaType('text', 'html'),
                        ],
                        [
                            'url' => 'http://example.com/style.css',
                            'type' => Source::TYPE_UNAVAILABLE,
                            'value' => 'http:500',
                        ],
                    ],
                ],
                'wrapperLocalSourceMap' => new SourceMap([
                    new SourceMapSource('http://example.com/', 'file:/tmp/web-page-hash.html'),
                ]),
                'cssValidatorOutput' => CssValidatorFixtureFactory::load('no-messages', [
                    '{{ webPageMappedUri }}' => 'file:/tmp/web-page-hash.html',
                ]),
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    [
                        'message' => 'http-retrieval-500',
                        'type' => 'error',
                        'context' => '',
                        'ref' => 'http://example.com/style.css',
                        'line_number' => 0,
                    ],
                ],
            ],
            'invalid content type on linked resource' => [
                'taskValues' => [
                    'url' => 'http://example.com/',
                    'parameters' => json_encode([
                        'ignore-warnings' => true,
                    ]),
                    'sources' => [
                        [
                            'type' => Source::TYPE_CACHED_RESOURCE,
                            'url' => 'http://example.com/',
                            'content' => HtmlDocumentFactory::load('empty-body-single-css-link'),
                            'contentType' => new InternetMediaType('text', 'html'),
                        ],
                        [
                            'url' => 'http://example.com/style.css',
                            'type' => Source::TYPE_INVALID,
                            'value' => 'invalid:invalid-content-type:application/pdf',
                        ],
                    ],
                ],
                'wrapperLocalSourceMap' => new SourceMap([
                    new SourceMapSource('http://example.com/', 'file:/tmp/web-page-hash.html'),
                ]),
                'cssValidatorOutput' => CssValidatorFixtureFactory::load('no-messages', [
                    '{{ webPageMappedUri }}' => 'file:/tmp/web-page-hash.html',
                ]),
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    [
                        'message' => 'invalid-content-type:application/pdf',
                        'type' => 'error',
                        'context' => '',
                        'ref' => 'http://example.com/style.css',
                        'line_number' => 0,
                    ],
                ],
            ],
        ];
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
