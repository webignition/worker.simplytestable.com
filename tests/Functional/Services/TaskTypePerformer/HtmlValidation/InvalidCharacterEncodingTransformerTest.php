<?php

namespace App\Tests\Functional\Services\TaskTypePerformer\HtmlValidation;

use App\Services\TaskTypePerformer\HtmlValidation\InvalidCharacterEncodingOutputTransformer;
use App\Tests\Functional\Services\TaskTypePerformer\AbstractWebPageTaskTypePerformerTest;

class InvalidCharacterEncodingTransformerTest extends AbstractWebPageTaskTypePerformerTest
{
    /**
     * @var InvalidCharacterEncodingOutputTransformer
     */
    private $taskTypePerformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->taskTypePerformer = self::$container->get(
            InvalidCharacterEncodingOutputTransformer::class
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
