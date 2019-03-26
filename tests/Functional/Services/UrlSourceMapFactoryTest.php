<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services;

use App\Model\Source;
use App\Model\Task\TypeInterface;
use App\Services\UrlSourceMapFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\TestTaskFactory;
use webignition\CssValidatorWrapper\SourceType;
use webignition\InternetMediaType\InternetMediaType;
use webignition\ResourceStorage\SourcePurger;
use webignition\UrlSourceMap\Source as SourceMapSource;
use webignition\UrlSourceMap\SourceMap;

class UrlSourceMapFactoryTest extends AbstractBaseTestCase
{
    /**
     * @var TestTaskFactory
     */
    private $testTaskFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->testTaskFactory = self::$container->get(TestTaskFactory::class);
    }

    /**
     * @dataProvider createForTaskDataProvider
     */
    public function testCreateForTask(
        array $taskValues,
        SourceMap $expectedSources,
        array $expectedSourceContents
    ) {
        $task = $this->testTaskFactory->create($taskValues);

        /* @var UrlSourceMapFactory $urlSourceMapFactory */
        $urlSourceMapFactory = self::$container->get(UrlSourceMapFactory::class);

        $sourceMap = $urlSourceMapFactory->createForTask($task);

        $this->assertInstanceOf(SourceMap::class, $sourceMap);
        $this->assertCount(count($expectedSources), $sourceMap);

        foreach ($expectedSources as $expectedSourceIndex => $expectedSource) {
            $source = $sourceMap[$expectedSourceIndex];

            $this->assertInstanceOf(SourceMapSource::class, $source);

            if ($source instanceof SourceMapSource) {
                if (null === $expectedSource->getMappedUri()) {
                    $this->assertNull($source->getMappedUri());
                } else {
                    $mappedUri = (string) $source->getMappedUri();

                    $this->assertRegExp($expectedSource->getMappedUri(), $mappedUri);

                    $localPath = (string) preg_replace('/^file:/', '', $mappedUri);

                    $expectedSourceContent = $expectedSourceContents[$expectedSourceIndex];
                    $this->assertEquals($expectedSourceContent, file_get_contents($localPath));

                    $this->assertEquals($expectedSource->getType(), $source->getType());
                }
            }
        }

        $sourcePurger = new SourcePurger();
        $sourcePurger->purgeLocalResources($sourceMap);
    }

    public function createForTaskDataProvider(): array
    {
        return [
            'no sources' => [
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'type' => TypeInterface::TYPE_CSS_VALIDATION,
                ]),
                'expectedSources' => new SourceMap(),
                'expectedSourceContents' => [],
            ],
            'invalid and unavailable sources' => [
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'type' => TypeInterface::TYPE_CSS_VALIDATION,
                    'sources' => [
                        [
                            'url' => 'http://example.com/foo',
                            'type' => Source::TYPE_UNAVAILABLE,
                            'value' => 'http:404',
                        ],
                        [
                            'url' => 'http://example.com/bar',
                            'type' => Source::TYPE_INVALID,
                            'value' => 'invalid:' . Source::MESSAGE_INVALID_CONTENT_TYPE,
                        ],
                    ],
                ]),
                'expectedSources' => new SourceMap([
                    'http://example.com/foo' => new SourceMapSource(
                        'http://example.com/foo'
                    ),
                    'http://example.com/bar' => new SourceMapSource(
                        'http://example.com/bar'
                    ),
                ]),
                'expectedSourceContents' => [],
            ],
            'available source, no css resources' => [
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'type' => TypeInterface::TYPE_CSS_VALIDATION,
                    'sources' => [
                        [
                            'url' => 'http://example.com/',
                            'type' => Source::TYPE_CACHED_RESOURCE,
                            'content' => '<!doctype html>',
                            'contentType' => new InternetMediaType('text', 'html'),
                        ],
                    ],
                ]),
                'expectedSources' => new SourceMap(),
                'expectedSourceContents' => [],
            ],
            'available source, has css resources' => [
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'type' => TypeInterface::TYPE_CSS_VALIDATION,
                    'sources' => [
                        [
                            'url' => 'http://example.com/',
                            'type' => Source::TYPE_CACHED_RESOURCE,
                            'content' => '<!doctype html>',
                            'contentType' => new InternetMediaType('text', 'html'),
                        ],
                        [
                            'url' => 'http://example.com/one.css',
                            'type' => Source::TYPE_CACHED_RESOURCE,
                            'content' => 'html {}',
                            'contentType' => new InternetMediaType('text', 'css'),
                            'context' => [
                                'origin' => 'resource',
                            ],
                        ],
                        [
                            'url' => 'http://example.com/two.css',
                            'type' => Source::TYPE_CACHED_RESOURCE,
                            'content' => 'body {}',
                            'contentType' => new InternetMediaType('text', 'css'),
                            'context' => [
                                'origin' => 'import',
                            ],
                        ],
                    ],
                ]),
                'expectedSources' => new SourceMap([
                    'http://example.com/one.css' => new SourceMapSource(
                        'http://example.com/one.css',
                        '/^file:\/tmp\/[a-f0-9]{32}\.css/'
                    ),
                    'http://example.com/two.css' => new SourceMapSource(
                        'http://example.com/two.css',
                        '/^file:\/tmp\/[a-f0-9]{32}\.css/',
                        SourceType::TYPE_IMPORT
                    ),
                ]),
                'expectedSourceContents' => [
                    'http://example.com/one.css' => 'html {}',
                    'http://example.com/two.css' => 'body {}',
                ],
            ],
            'available source, has css resources, one ignored' => [
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'type' => TypeInterface::TYPE_CSS_VALIDATION,
                    'parameters' => json_encode([
                        'domains-to-ignore' => [
                            'foo.example.com',
                        ],
                    ]),
                    'sources' => [
                        [
                            'url' => 'http://example.com/',
                            'type' => Source::TYPE_CACHED_RESOURCE,
                            'content' => '<!doctype html>',
                            'contentType' => new InternetMediaType('text', 'html'),
                        ],
                        [
                            'url' => 'http://foo.example.com/one.css',
                            'type' => Source::TYPE_CACHED_RESOURCE,
                            'content' => 'html {}',
                            'contentType' => new InternetMediaType('text', 'css'),
                            'context' => [
                                'origin' => 'resource',
                            ],
                        ],
                        [
                            'url' => 'http://bar.example.com/two.css',
                            'type' => Source::TYPE_CACHED_RESOURCE,
                            'content' => 'body {}',
                            'contentType' => new InternetMediaType('text', 'css'),
                            'context' => [
                                'origin' => 'import',
                            ],
                        ],
                    ],
                ]),
                'expectedSources' => new SourceMap([
                    'http://foo.example.com/one.css' => new SourceMapSource(
                        'http://foo.example.com/one.css'
                    ),
                    'http://bar.example.com/two.css' => new SourceMapSource(
                        'http://bar.example.com/two.css',
                        '/^file:\/tmp\/[a-f0-9]{32}\.css/',
                        SourceType::TYPE_IMPORT
                    ),
                ]),
                'expectedSourceContents' => [
                    'http://bar.example.com/two.css' => 'body {}',
                ],
            ],
            'available source, has css resources, both ignored' => [
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    'type' => TypeInterface::TYPE_CSS_VALIDATION,
                    'parameters' => json_encode([
                        'domains-to-ignore' => [
                            'foo.example.com',
                            'bar.example.com',
                        ],
                    ]),
                    'sources' => [
                        [
                            'url' => 'http://example.com/',
                            'type' => Source::TYPE_CACHED_RESOURCE,
                            'content' => '<!doctype html>',
                            'contentType' => new InternetMediaType('text', 'html'),
                        ],
                        [
                            'url' => 'http://foo.example.com/one.css',
                            'type' => Source::TYPE_CACHED_RESOURCE,
                            'content' => 'html {}',
                            'contentType' => new InternetMediaType('text', 'css'),
                            'context' => [
                                'origin' => 'resource',
                            ],
                        ],
                        [
                            'url' => 'http://bar.example.com/two.css',
                            'type' => Source::TYPE_CACHED_RESOURCE,
                            'content' => 'body {}',
                            'contentType' => new InternetMediaType('text', 'css'),
                            'context' => [
                                'origin' => 'import',
                            ],
                        ],
                    ],
                ]),
                'expectedSources' => new SourceMap([
                    'http://foo.example.com/one.css' => new SourceMapSource(
                        'http://foo.example.com/one.css'
                    ),
                    'http://bar.example.com/two.css' => new SourceMapSource(
                        'http://bar.example.com/two.css'
                    ),
                ]),
                'expectedSourceContents' => [],
            ],
        ];
    }
}
