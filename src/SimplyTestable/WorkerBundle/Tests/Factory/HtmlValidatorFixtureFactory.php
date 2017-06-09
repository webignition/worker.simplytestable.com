<?php

namespace SimplyTestable\WorkerBundle\Tests\Factory;

use phpmock\mockery\PHPMockery;

class HtmlValidatorFixtureFactory
{
    /**
     * @param string $fixture
     */
    public static function set($fixture)
    {
        PHPMockery::mock(
            'webignition\HtmlValidator\Wrapper',
            'shell_exec'
        )->andReturn(
            $fixture
        );
    }
}
