<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services;

use App\Entity\Task\Task;
use App\Model\CssSourceUrl;
use App\Model\Task\TypeInterface;
use App\Services\WebPageTaskCssUrlFinder;
use App\Tests\Factory\HtmlDocumentFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\TestTaskFactory;
use webignition\InternetMediaType\InternetMediaType;

class WebPageTaskCssUrlFinderTest extends AbstractBaseTestCase
{
    /**
     * @var WebPageTaskCssUrlFinder
     */
    private $webPageTaskCssUrlFinder;

    /**
     * @var TestTaskFactory
     */
    private $testTaskFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->webPageTaskCssUrlFinder = self::$container->get(WebPageTaskCssUrlFinder::class);
        $this->testTaskFactory = self::$container->get(TestTaskFactory::class);
    }

    /**
     * @dataProvider findDataProvider
     */
    public function testFind(array $taskValues, array $expectedCssSourceUrls)
    {
        $task = $this->testTaskFactory->create($taskValues);

        $this->assertEquals($expectedCssSourceUrls, $this->webPageTaskCssUrlFinder->find($task));
    }

    public function findDataProvider(): array
    {
        return [
            'no urls' => [
                'taskValues' => [
                    'url' => 'http://example.com',
                    'type' =>  TypeInterface::TYPE_CSS_VALIDATION,
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
                'expectedUrls' => [],
            ],
            'single linked stylesheet' => [
                'taskValues' => [
                    'url' => 'http://example.com',
                    'type' =>  TypeInterface::TYPE_CSS_VALIDATION,
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
                'expectedUrls' => [
                    new CssSourceUrl('http://example.com/style.css', CssSourceUrl::TYPE_RESOURCE),
                ],
            ],
            'single linked stylesheet, single import, none sourced' => [
                'taskValues' => [
                    'url' => 'http://example.com',
                    'type' =>  TypeInterface::TYPE_CSS_VALIDATION,
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
                'expectedUrls' => [
                    new CssSourceUrl('http://example.com/one.css', CssSourceUrl::TYPE_RESOURCE),
                    new CssSourceUrl('http://example.com/two.css', CssSourceUrl::TYPE_RESOURCE),
                ],
            ],
            'single linked stylesheet, single import, linked stylesheet sourced, no additional imports' => [
                'taskValues' => [
                    'url' => 'http://example.com',
                    'type' =>  TypeInterface::TYPE_CSS_VALIDATION,
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
                'expectedUrls' => [
                    new CssSourceUrl('http://example.com/one.css', CssSourceUrl::TYPE_RESOURCE),
                    new CssSourceUrl('http://example.com/two.css', CssSourceUrl::TYPE_RESOURCE),
                ],
            ],
            'single linked stylesheet, single import, linked stylesheet sourced, additional imports in stylesheet' => [
                'taskValues' => [
                    'url' => 'http://example.com',
                    'type' =>  TypeInterface::TYPE_CSS_VALIDATION,
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
                            'content' => '@import url("three.css");',
                            'contentType' => new InternetMediaType('text', 'css'),
                        ],
                    ],
                ],
                'expectedUrls' => [
                    new CssSourceUrl('http://example.com/one.css', CssSourceUrl::TYPE_RESOURCE),
                    new CssSourceUrl('http://example.com/two.css', CssSourceUrl::TYPE_RESOURCE),
                    new CssSourceUrl('http://example.com/three.css', CssSourceUrl::TYPE_IMPORT),
                ],
            ],
            'single linked stylesheet, single import, linked stylesheet sourced, duplicate imports in stylesheet' => [
                'taskValues' => [
                    'url' => 'http://example.com',
                    'type' =>  TypeInterface::TYPE_CSS_VALIDATION,
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
                            'content' => implode("\n", [
                                '@import url("one.css");',
                                '@import url("two.css");',
                                '@import url("three.css");',
                            ]),
                            'contentType' => new InternetMediaType('text', 'css'),
                        ],
                    ],
                ],
                'expectedUrls' => [
                    new CssSourceUrl('http://example.com/one.css', CssSourceUrl::TYPE_RESOURCE),
                    new CssSourceUrl('http://example.com/two.css', CssSourceUrl::TYPE_RESOURCE),
                    new CssSourceUrl('http://example.com/three.css', CssSourceUrl::TYPE_IMPORT),
                ],
            ],
        ];
    }
}
