<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services;

use App\Model\Source;
use App\Services\CssValidatorErrorFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use webignition\CssValidatorOutput\Model\ErrorMessage;

class CssValidatorErrorFactoryTest extends AbstractBaseTestCase
{
    /**
     * @var CssValidatorErrorFactory
     */
    private $cssValidatorErrorFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->cssValidatorErrorFactory = self::$container->get(CssValidatorErrorFactory::class);
    }

    /**
     * @dataProvider createForUnavailableTaskSourceDataProvider
     */
    public function testCreateForUnavailableTaskSource(Source $source, ErrorMessage $expectedErrorMessage)
    {
        $this->assertEquals(
            $expectedErrorMessage,
            $this->cssValidatorErrorFactory->createForUnavailableTaskSource($source)
        );
    }

    public function createForUnavailableTaskSourceDataProvider(): array
    {
        return [
            'http 404' => [
                'source' => new Source(
                    'http://example.com/style.css',
                    Source::TYPE_UNAVAILABLE,
                    'http:404'
                ),
                'expectedErrorMessage' => new ErrorMessage(
                    'http-retrieval-404',
                    0,
                    '',
                    'http://example.com/style.css'
                ),
            ],
            'curl 6' => [
                'source' => new Source(
                    'http://example.com/style.css',
                    Source::TYPE_UNAVAILABLE,
                    'curl:6'
                ),
                'expectedErrorMessage' => new ErrorMessage(
                    'http-retrieval-curl-code-6',
                    0,
                    '',
                    'http://example.com/style.css'
                ),
            ],
            'invalid content type' => [
                'source' => new Source(
                    'http://example.com/style.css',
                    Source::TYPE_INVALID,
                    'invalid' . ':invalid-content-type:application/pdf'
                ),
                'expectedErrorMessage' => new ErrorMessage(
                    'invalid-content-type:application/pdf',
                    0,
                    '',
                    'http://example.com/style.css'
                ),
            ],
        ];
    }
}
