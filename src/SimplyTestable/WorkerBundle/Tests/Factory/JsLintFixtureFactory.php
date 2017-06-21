<?php

namespace SimplyTestable\WorkerBundle\Tests\Factory;

use phpmock\mockery\PHPMockery;

class JsLintFixtureFactory
{
    /**
     * @param string[] $fixtures
     */
    public static function set($fixtures)
    {
        PHPMockery::mock(
            'webignition\NodeJslint\Wrapper',
            'shell_exec'
        )->andReturnValues(
            $fixtures
        );
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public static function load($name)
    {
        return file_get_contents(__DIR__ . '/../Fixtures/Data/RawJsLintOutput/' . $name);
    }
}
