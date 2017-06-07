<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\CssValidation;

use phpmock\mockery\PHPMockery;
use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\BaseTest;

abstract class TaskDriverTest extends BaseTest {

    protected function getTaskTypeName() {
        return 'CSS Validation';
    }

    /**
     * @param string $fixture
     */
    protected function setCssValidatorFixture($fixture)
    {
        PHPMockery::mock(
            'webignition\CssValidatorWrapper',
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
