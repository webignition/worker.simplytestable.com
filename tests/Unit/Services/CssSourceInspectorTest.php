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
}
