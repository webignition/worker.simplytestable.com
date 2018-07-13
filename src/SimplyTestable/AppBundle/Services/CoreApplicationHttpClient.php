<?php

namespace SimplyTestable\AppBundle\Services;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class CoreApplicationHttpClient
{
    /**
     * @var CoreApplicationRouter
     */
    private $coreApplicationRouter;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @param CoreApplicationRouter $coreApplicationRouter
     * @param HttpClient $httpClient
     */
    public function __construct(CoreApplicationRouter $coreApplicationRouter, HttpClient $httpClient)
    {
        $this->coreApplicationRouter = $coreApplicationRouter;
        $this->httpClient = $httpClient;
    }

    /**
     * @param string $routeName
     * @param array $routeParameters
     * @param array $postData
     *
     * @return Request
     */
    public function createPostRequest($routeName, array $routeParameters, array $postData)
    {
        $requestUrl = $this->coreApplicationRouter->generate($routeName, $routeParameters);

        return new Request(
            'POST',
            $requestUrl,
            ['content-type' => 'application/x-www-form-urlencoded'],
            http_build_query($postData, '', '&')
        );
    }

    /**
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     * @throws GuzzleException
     */
    public function send(RequestInterface $request)
    {
        return $this->httpClient->send($request);
    }
}
