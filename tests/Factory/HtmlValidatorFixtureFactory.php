<?php

namespace App\Tests\Factory;

use phpmock\mockery\PHPMockery;

class HtmlValidatorFixtureFactory
{
    public static function set(string $fixture)
    {
        PHPMockery::mock(
            'webignition\HtmlValidator\Wrapper',
            'shell_exec'
        )->andReturn(
            $fixture
        );
    }

    public static function load(string $name): string
    {
        return (string) file_get_contents(__DIR__ . '/../Fixtures/Data/RawHtmlValidatorOutput/' . $name . '.txt');
    }
}
