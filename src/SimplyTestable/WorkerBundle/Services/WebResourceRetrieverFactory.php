<?php

namespace SimplyTestable\WorkerBundle\Services;

use GuzzleHttp\Client as HttpClient;
use webignition\WebResource\Retriever;
use webignition\WebResource\WebPage\WebPage;

class WebResourceRetrieverFactory
{
    const ALLOW_UNKNOWN_RESOURCE_TYPES = false;

    /**
     * @param HttpClient $httpClient
     *
     * @return Retriever
     */
    public static function create(HttpClient $httpClient)
    {
        $allowedContentTypes = array_merge(
            WebPage::getModelledContentTypeStrings(),
            [
                'text/javascript',
                'application/javascript',
                'application/x-javascript',
            ]
        );

        return new Retriever($httpClient, $allowedContentTypes, self::ALLOW_UNKNOWN_RESOURCE_TYPES);
    }
}
