<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\HtmlValidation;

use phpmock\mockery\PHPMockery;
use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\BaseTest;

abstract class TaskDriverTest extends BaseTest {

    protected function getTaskTypeName() {
        return 'HTML Validation';
    }

    /**
     * @param string $fixture
     */
    protected function setHtmlValidatorFixture($fixture)
    {
        PHPMockery::mock(
            'webignition\HtmlValidator\Wrapper',
            'shell_exec'
        )->andReturn(
            $fixture
        );
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
