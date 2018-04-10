<?php

namespace SimplyTestable\WorkerBundle\Services;

use GuzzleHttp\Client as HttpClient;
use webignition\WebResource\Retriever;
use webignition\WebResource\WebPage\WebPage;

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
        $allowedContentTypes = array_merge(
            WebPage::getModelledContentTypeStrings(),
            [
                'text/javascript',
                'application/javascript',
                'application/x-javascript',
            ]
        );

        return new Retriever(
            $this->httpClient,
            $allowedContentTypes,
            self::ALLOW_UNKNOWN_RESOURCE_TYPES
        );
    }
}
