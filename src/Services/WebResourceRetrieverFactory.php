<?php

namespace App\Services;

use GuzzleHttp\Client as HttpClient;
use webignition\WebResource\Retriever;

class WebResourceRetrieverFactory
{
    const ALLOW_UNKNOWN_RESOURCE_TYPES = false;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @param HttpClient $httpClient
     */
    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @return Retriever
     */
    public function create()
    {
        return new Retriever(
            $this->httpClient,
            [
                'text/html',
                'application/xhtml+xml',
            ],
            self::ALLOW_UNKNOWN_RESOURCE_TYPES
        );
    }
}
