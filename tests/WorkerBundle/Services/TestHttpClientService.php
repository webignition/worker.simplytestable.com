<?php

namespace Tests\WorkerBundle\Services;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use SimplyTestable\WorkerBundle\Services\HttpCache;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class TestHttpClientService extends HttpClientService
{
    /**
     * @var MockHandler
     */
    private $mockHandler;

    /**
     * @param array $curlOptions
     * @param HandlerStack $handlerStack
     * @param HttpCache $cache
     * @param HttpHistoryContainer $httpHistory
     * @param HttpMockHandler $httpMockHandler
     */
    public function __construct(
        array $curlOptions,
        HandlerStack $handlerStack,
        HttpCache $cache,
        HttpHistoryContainer $httpHistory,
        HttpMockHandler $httpMockHandler
    ) {
        $this->mockHandler = $httpMockHandler;

        parent::__construct($curlOptions, $handlerStack, $cache, $httpHistory);
    }
}
