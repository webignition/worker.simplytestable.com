<?php

namespace Tests\WorkerBundle\Services;

use GuzzleHttp\Handler\MockHandler;
use SimplyTestable\WorkerBundle\Services\HttpCache;
use SimplyTestable\WorkerBundle\Services\HttpClientService;

class TestHttpClientService extends HttpClientService
{
    /**
     * @var MockHandler
     */
    private $mockHandler;

    /**
     * @param array $curlOptions
     * @param HttpCache $cache
     * @param HttpMockHandler $httpMockHandler
     */
    public function __construct(array $curlOptions, HttpCache $cache, HttpMockHandler $httpMockHandler)
    {
        $this->mockHandler = $httpMockHandler;

        parent::__construct($curlOptions, $cache);
    }

    /**
     * @return MockHandler
     */
    protected function createInitialHandler()
    {
        parent::createInitialHandler();

        return $this->mockHandler;
    }
}
