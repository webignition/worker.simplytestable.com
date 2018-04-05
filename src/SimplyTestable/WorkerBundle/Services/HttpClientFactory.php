<?php

namespace SimplyTestable\WorkerBundle\Services;

use GuzzleHttp\Client as HttpClient;

class HttpClientFactory
{
    const ALLOW_UNKNOWN_RESOURCE_TYPES = false;

    /**
     * @param array $curlOptions
     *
     * @return HttpClient
     */
    public static function create(array $curlOptions)
    {
        $definedCurlOptions = [];

        foreach ($curlOptions as $curlOption) {
            if (defined($curlOption['name'])) {
                $definedCurlOptions[constant($curlOption['name'])] = $curlOption['value'];
            }
        }

        return new HttpClient([
            'config' => [
                'curl' => $definedCurlOptions
            ],
            'defaults' => [
                'verify' => false,
            ],
        ]);
    }
}
