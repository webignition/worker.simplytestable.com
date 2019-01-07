<?php

namespace App\Tests\Functional\Services\TaskTypePerformer;

use App\Services\TaskTypePerformer\HtmlValidationInvalidCharacterEncodingOutputTransformer;

class HtmlValidationInvalidCharacterEncodingTransformerTest extends AbstractWebPageTaskTypePerformerTest
{
    /**
     * @var HtmlValidationInvalidCharacterEncodingOutputTransformer
     */
    private $taskTypePerformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->taskTypePerformer = self::$container->get(
            HtmlValidationInvalidCharacterEncodingOutputTransformer::class
        );
    }

    public function testGetPriority()
    {
        $this->assertEquals(
            self::$container->getParameter('html_validation_invalid_character_encoding_output_transformer_priority'),
            $this->taskTypePerformer->getPriority()
        );
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
