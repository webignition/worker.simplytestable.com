<?php

namespace App\Tests\Services;

use GuzzleHttp\Handler\MockHandler;

class HttpMockHandler extends MockHandler
{
    /**
     * @param array $fixtures
     */
    public function appendFixtures(array $fixtures)
    {
        foreach ($fixtures as $fixture) {
            $this->append($fixture);
        }
    }
}
