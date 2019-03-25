<?php

namespace App\Tests\Factory;

use phpmock\mockery\PHPMockery;

class CssValidatorFixtureFactory
{
    public static function set(string $fixture)
    {
        PHPMockery::mock(
            'webignition\CssValidatorWrapper',
            'shell_exec'
        )->andReturn(
            $fixture
        );
    }


    public static function load(string $name, array $replacements = []): string
    {
        $content = (string) file_get_contents(__DIR__ . '/../Fixtures/Data/RawCssValidatorOutput/' . $name . '.txt');

        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }

        return $content;
    }
}
