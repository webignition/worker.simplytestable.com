<?php
/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Unit\Services;

use App\Services\CssSourceInspector;
use App\Tests\Factory\HtmlDocumentFactory;
use webignition\WebResource\WebPage\WebPage;

class CssSourceInspectorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CssSourceInspector
     */
    private $cssSourceInspector;

    protected function setUp()
    {
        parent::setUp();

        $this->cssSourceInspector = new CssSourceInspector();
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
                    HtmlDocumentFactory::load('style-elements')
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
        ];
    }
}




