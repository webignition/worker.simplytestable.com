<?php
/** @noinspection PhpDocMissingThrowsInspection */
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Unit\Services;

use App\Model\CssSourceUrl;
use App\Services\CssSourceInspector;
use App\Tests\Factory\HtmlDocumentFactory;
use webignition\CssValidatorWrapper\SourceInspector;
use webignition\Uri\Uri;
use webignition\WebResource\WebPage\WebPage;
use webignition\WebResourceInterfaces\WebResourceInterface;

class CssSourceInspectorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CssSourceInspector
     */
    private $cssSourceInspector;

    protected function setUp()
    {
        parent::setUp();

        $this->cssSourceInspector = new CssSourceInspector(new SourceInspector());
    }

    /**
     * @dataProvider findStyleBlocksDataProvider
     */
    public function testFindStyleBlocks(WebPage $webPage, array $expectedStyleBlocks)
    {
        $styleBlocks = $this->cssSourceInspector->findStyleBlocks($webPage);

        $this->assertEquals($expectedStyleBlocks, $styleBlocks);
    }

    public function findStyleBlocksDataProvider(): array
    {
        return [
            'no style blocks' => [
                'webPage' => WebPage::createFromContent(
                    HtmlDocumentFactory::load('minimal')
                ),
                'expectedStyleBlocks' => [],
            ],
            'empty and non-empty style blocks in head and body' => [
                'webPage' => WebPage::createFromContent(
                    HtmlDocumentFactory::load('style-elements-no-imports')
                ),
                'expectedStyleBlocks' => [
                    'html {}',
                    'p {
                color: red;
            }',
                    'body {}',
                    'blockquote {
                color: blue;
            }',
                ],
            ],
        ];
    }

    /**
     * @dataProvider findImportValuesDataProvider
     */
    public function testFindImportValues(string $css, array $expectedImportValues)
    {
        $importValues = $this->cssSourceInspector->findImportValues($css);

        $this->assertEquals($expectedImportValues, $importValues);
    }

    public function findImportValuesDataProvider(): array
    {
        return [
            'empty' => [
                'css' => '',
                'expectedImportValues' => [],
            ],
            'no import values' => [
                'css' => 'html {}',
                'expectedImportValues' => [],
            ],
            'single import (url, double-quoted)' => [
                'css' => '@import url("style.css");',
                'expectedImportValues' => [
                    'style.css',
                ],
            ],
            'single import (url, single-quoted)' => [
                'css' => "@import url('style.css');",
                'expectedImportValues' => [
                    'style.css',
                ],
            ],
            'single import (url, unquoted)' => [
                'css' => '@import url(style.css);',
                'expectedImportValues' => [
                    'style.css',
                ],
            ],
            'single import (string, double-quoted)' => [
                'css' => '@import "style.css";',
                'expectedImportValues' => [
                    'style.css',
                ],
            ],
            'single import (string single-quoted)' => [
                'css' => "@import 'style.css';",
                'expectedImportValues' => [
                    'style.css',
                ],
            ],
            'charset, import' => [
                'css' => '@charset "utf-8";@import url("style.css");',
                'expectedImportValues' => [
                    'style.css',
                ],
            ],
            'import, import' => [
                'css' => '@import url("one.css");@import url("two.css");',
                'expectedImportValues' => [
                    'one.css',
                    'two.css',
                ],
            ],
            'duplicated import' => [
                'css' => '@import url("style.css");@import url("style.css");',
                'expectedImportValues' => [
                    'style.css',
                ],
            ],
            'import, css, import' => [
                'css' => implode("\n", [
                    '@import url("one.css");',
                    'html {}',
                    '@import url("two.css");',
                ]),
                'expectedImportValues' => [
                    'one.css',
                ],
            ],
            'import, charset, import (charset incorrectly ignored by CSS parser)' => [
                'css' => implode("\n", [
                    '@import "one.css";',
                    '@charset "utf-8";',
                    '@import "two.css";',
                ]),
                'expectedImportValues' => [
                    'one.css',
                    'two.css',
                ],
            ],
            'invalid css (unexpected end of document); no leading content' => [
                'css' => implode("\n", [
                    '@media all and (min-width: 32em) {',
                    '  html {',
                    '  }',
                ]),
                'expectedImportValues' => [],
            ],
            'invalid css (unexpected end of document); leading charset' => [
                'css' => implode("\n", [
                    '@charset "utf-8";',
                    '@media all and (min-width: 32em) {',
                    '  html {',
                    '  }',
                ]),
                'expectedImportValues' => [],
            ],
            'invalid css (unexpected end of document); leading import' => [
                'css' => implode("\n", [
                    '@import url("import.css");',
                    '@media all and (min-width: 32em) {',
                    '  html {',
                    '  }',
                ]),
                'expectedImportValues' => [],
            ],
        ];
    }

    /**
     * @dataProvider createImportUrlsDataProvider
     */
    public function testCreateImportUrls(array $importValues, string $baseUrl, array $expectedUrls)
    {
        $importUrls = $this->cssSourceInspector->createImportUrls($importValues, $baseUrl);

        $this->assertEquals($expectedUrls, $importUrls);
    }

    public function createImportUrlsDataProvider(): array
    {
        return [
            'empty' => [
                'importValues' => [],
                'baseUrl' => 'http://example.com/',
                'expectedUrls' => [],
            ],
            'collection (1)' => [
                'importValues' => [
                    'one.css',
                    '/two.css',
                ],
                'baseUrl' => 'http://example.com/foo/',
                'expectedUrls' => [
                    'http://example.com/foo/one.css',
                    'http://example.com/two.css',
                ],
            ],
            'collection (2)' => [
                'importValues' => [
                    'one.css',
                    '/two.css',
                ],
                'baseUrl' => 'http://example.com/',
                'expectedUrls' => [
                    'http://example.com/one.css',
                    'http://example.com/two.css',
                ],
            ],
        ];
    }

    /**
     * @dataProvider findWebPageImportUrlsDataProvider
     */
    public function testFindWebPageImportUrls(WebPage $webPage, array $expectedUrls)
    {
        $importUrls = $this->cssSourceInspector->findWebPageImportUrls($webPage);

        $this->assertEquals($expectedUrls, $importUrls);
    }

    public function findWebPageImportUrlsDataProvider(): array
    {
        return [
            'empty' => [
                'webPage' => $this->createWebPage('', 'http://example.com/'),
                'expectedUrls' => [],
            ],
            'many style elements, no import urls' => [
                'webPage' => $this->createWebPage(
                    HtmlDocumentFactory::load('style-elements-no-imports'),
                    'http://example.com/'
                ),
                'expectedUrls' => [],
            ],
            'many style elements, has import urls, http://example.com/' => [
                'webPage' => $this->createWebPage(
                    HtmlDocumentFactory::load('style-elements-has-imports'),
                    'http://example.com/'
                ),
                'expectedUrls' => [
                    'http://example.com/one.css',
                    'http://example.com/two.css',
                    'http://example.com/three.css',
                ],
            ],
            'many style elements, has import urls, http://example.com/foo/' => [
                'webPage' => $this->createWebPage(
                    HtmlDocumentFactory::load('style-elements-has-imports'),
                    'http://example.com/foo/'
                ),
                'expectedUrls' => [
                    'http://example.com/foo/one.css',
                    'http://example.com/two.css',
                    'http://example.com/foo/three.css',
                ],
            ],
        ];
    }

    /**
     * @dataProvider findStylesheetUrlsDataProvider
     */
    public function testFindStylesheetUrls(WebPage $webPage, array $expectedCssSourceUrls)
    {
        $cssSourceUrls = $this->cssSourceInspector->findStylesheetUrls($webPage);

        $this->assertEquals($expectedCssSourceUrls, $cssSourceUrls);
    }

    public function findStylesheetUrlsDataProvider()
    {
        return [
            'empty' => [
                'webPage' => $this->createWebPage('', 'http://example.com/'),
                'expectedCssSourceUrls' => [],
            ],
            'many style elements, no import urls' => [
                'webPage' => $this->createWebPage(
                    HtmlDocumentFactory::load('style-elements-no-imports'),
                    'http://example.com/'
                ),
                'expectedCssSourceUrls' => [],
            ],
            'many style elements, has import urls, http://example.com/' => [
                'webPage' => $this->createWebPage(
                    HtmlDocumentFactory::load('style-elements-has-imports'),
                    'http://example.com/'
                ),
                'expectedCssSourceUrls' => [
                    'http://example.com/one.css' => new CssSourceUrl(
                        'http://example.com/one.css',
                        CssSourceUrl::TYPE_IMPORT
                    ),
                    'http://example.com/two.css' => new CssSourceUrl(
                        'http://example.com/two.css',
                        CssSourceUrl::TYPE_IMPORT
                    ),
                    'http://example.com/three.css' => new CssSourceUrl(
                        'http://example.com/three.css',
                        CssSourceUrl::TYPE_IMPORT
                    ),
                ],
            ],
            'many style elements, has import urls, http://example.com/foo/' => [
                'webPage' => $this->createWebPage(
                    HtmlDocumentFactory::load('style-elements-has-imports'),
                    'http://example.com/foo/'
                ),
                'expectedCssSourceUrls' => [
                    'http://example.com/foo/one.css' => new CssSourceUrl(
                        'http://example.com/foo/one.css',
                        CssSourceUrl::TYPE_IMPORT
                    ),
                    'http://example.com/two.css' => new CssSourceUrl(
                        'http://example.com/two.css',
                        CssSourceUrl::TYPE_IMPORT
                    ),
                    'http://example.com/foo/three.css' => new CssSourceUrl(
                        'http://example.com/foo/three.css',
                        CssSourceUrl::TYPE_IMPORT
                    ),
                ],
            ],
            'single linked stylesheet' => [
                'webPage' => $this->createWebPage(
                    HtmlDocumentFactory::load('empty-body-single-css-link'),
                    'http://example.com/'
                ),
                'expectedCssSourceUrls' => [
                    'http://example.com/style.css' => new CssSourceUrl(
                        'http://example.com/style.css',
                        CssSourceUrl::TYPE_RESOURCE
                    ),
                ],
            ],
            'linked stylesheets and imports' => [
                'webPage' => $this->createWebPage(
                    HtmlDocumentFactory::load('style-elements-linked-stylesheets-has-imports'),
                    'http://example.com/'
                ),
                'expectedCssSourceUrls' => [
                    'http://example.com/one.css' => new CssSourceUrl(
                        'http://example.com/one.css',
                        CssSourceUrl::TYPE_RESOURCE
                    ),
                    'http://example.com/two.css' => new CssSourceUrl(
                        'http://example.com/two.css',
                        CssSourceUrl::TYPE_RESOURCE
                    ),
                    'http://example.com/three.css' => new CssSourceUrl(
                        'http://example.com/three.css',
                        CssSourceUrl::TYPE_IMPORT
                    ),
                    'http://example.com/four.css' => new CssSourceUrl(
                        'http://example.com/four.css',
                        CssSourceUrl::TYPE_IMPORT
                    ),
                ],
            ],
            'linked stylesheets, ie conditional comments and imports' => [
                'webPage' => $this->createWebPage(
                    HtmlDocumentFactory::load('linked-stylesheets-and-ie-conditional-stylesheets-and-import'),
                    'http://example.com/'
                ),
                'expectedCssSourceUrls' => [
                    'http://example.com/link.css' => new CssSourceUrl(
                        'http://example.com/link.css',
                        CssSourceUrl::TYPE_RESOURCE
                    ),
                    'http://example.com/conditional.css' => new CssSourceUrl(
                        'http://example.com/conditional.css',
                        CssSourceUrl::TYPE_IMPORT
                    ),
                    'http://example.com/downlevel-revealed.css' => new CssSourceUrl(
                        'http://example.com/downlevel-revealed.css',
                        CssSourceUrl::TYPE_RESOURCE
                    ),
                    'http://example.com/import.css' => new CssSourceUrl(
                        'http://example.com/import.css',
                        CssSourceUrl::TYPE_IMPORT
                    ),
                ],
            ],
        ];
    }

    /**
     * @dataProvider findCssImportUrlsDataProvider
     */
    public function testFindCssImportUrls(string $css, string $baseUrl, array $expectedUrls)
    {
        $importUrls = $this->cssSourceInspector->findCssImportUrls($css, $baseUrl);

        $this->assertEquals($expectedUrls, $importUrls);
    }

    public function findCssImportUrlsDataProvider(): array
    {
        return [
            'empty' => [
                'css' => '',
                'baseUrl' => 'http://example.com/',
                'expectedUrls' => [],
            ],
            'has import urls' => [
                'css' => implode("\n", [
                    '@import "one.css";',
                    "@import 'two.css';",
                    '@import url("three.css");',
                ]),
                'baseUrl' => 'http://example.com/',
                'expectedUrls' => [
                    'http://example.com/one.css',
                    'http://example.com/two.css',
                    'http://example.com/three.css',
                ],
            ],
        ];
    }

    private function createWebPage(string $content, string $url): WebPage
    {
        /* @var WebPage $webPage */
        $webPage = WebPage::createFromContent($content);
        $webPage = $webPage->setUri(new Uri($url));

        return $webPage;
    }
}
