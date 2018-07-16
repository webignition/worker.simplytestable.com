<?php

namespace App\Tests\Factory;

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

    /**
     * @param string $name
     *
     * @return string
     */
    public static function load($name)
    {
        return file_get_contents(__DIR__ . '/../Fixtures/Data/RawHtmlValidatorOutput/' . $name . '.txt');
    }
}
