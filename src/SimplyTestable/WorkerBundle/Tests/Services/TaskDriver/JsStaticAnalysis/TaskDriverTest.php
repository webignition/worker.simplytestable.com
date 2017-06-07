<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\JsStaticAnalysis;

use phpmock\mockery\PHPMockery;
use SimplyTestable\WorkerBundle\Tests\Services\TaskDriver\BaseTest;

abstract class TaskDriverTest extends BaseTest {

    protected function getTaskTypeName() {
        return 'JS Static Analysis';
    }

    /**
     * @param string $fixture
     */
    protected function setJsLintValidatorFixture($fixture)
    {
        PHPMockery::mock(
            'webignition\NodeJslint\Wrapper',
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
