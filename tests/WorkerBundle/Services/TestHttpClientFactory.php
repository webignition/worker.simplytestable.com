<?php

namespace Tests\WorkerBundle\Services;

use GuzzleHttp\Handler\MockHandler;
use SimplyTestable\WorkerBundle\Services\HttpClientFactory;

class TestHttpClientFactory extends HttpClientFactory
{
    /**
     * @var MockHandler
     */
    private $mockHandler;

    /**
     * @return MockHandler
     */
    protected function createInitialHandler()
    {
        $this->mockHandler = new MockHandler();

        return $this->getMockHandler();
    }

    /**
     * @return MockHandler
     */
    public function getMockHandler()
    {
        return $this->mockHandler;
    }
}
