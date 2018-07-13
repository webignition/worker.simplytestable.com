<?php

namespace Tests\AppBundle\Factory;

use phpmock\mockery\PHPMockery;

class CssValidatorFixtureFactory
{
    /**
     * @param string $fixture
     */
    public static function set($fixture)
    {
        PHPMockery::mock(
            'webignition\CssValidatorWrapper',
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
        return file_get_contents(__DIR__ . '/../Fixtures/Data/RawCssValidatorOutput/' . $name . '.txt');
    }
}
