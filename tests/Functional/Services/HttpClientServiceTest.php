<?php

namespace App\Tests\Functional\Services;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use App\Services\HttpClientService;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\HttpMockHandler;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationCredentials;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationMiddleware;
use webignition\Guzzle\Middleware\RequestHeaders\RequestHeadersMiddleware;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class HttpClientServiceTest extends AbstractBaseTestCase
{
    /**
     * @var HttpClientService
     */
    private $httpClientService;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var HttpMockHandler
     */
    private $httpMockHandler;

    /**
     * @var HttpHistoryContainer
     */
    private $httpHistoryContainer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->httpClientService = self::$container->get(HttpClientService::class);
        $this->httpClient = $this->httpClientService->getHttpClient();
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
        $this->httpHistoryContainer = self::$container->get(HttpHistoryContainer::class);
    }

    /**
     * @dataProvider setHeaderDataProvider
     *
     * @param string $name
     * @param string $value
     *
     * @throws GuzzleException
     */
    public function testSetHeader($name, $value)
    {
        $requestHeadersMiddleware = self::$container->get(RequestHeadersMiddleware::class);

        $this->httpMockHandler->appendFixtures([
            new Response(),
            new Response(),
            new Response(),
        ]);

        $requestHeadersMiddleware->setHeader($name, $value);

        $request = new Request('GET', 'http://example.com');

        $this->httpClient->send($request);
        $this->httpClient->send($request);
        $this->httpClient->send($request);

        $historicalRequests = $this->httpHistoryContainer->getRequests();

        foreach ($historicalRequests as $historicalRequest) {
            $this->assertEquals($value, $historicalRequest->getHeaderLine($name));
        }
    }

    /**
     * @return array
     */
    public function setHeaderDataProvider()
    {
        return [
            'empty name, empty value' => [
                'name' => '',
                'value' => '',
            ],
            'name, empty value' => [
                'name' => 'foo',
                'value' => '',
            ],
            'name, value' => [
                'name' => 'foo',
                'value' => 'bar',
            ],
        ];
    }

    /**
     * @throws GuzzleException
     */
    public function testClearHeader()
    {
        $requestHeadersMiddleware = self::$container->get(RequestHeadersMiddleware::class);

        $this->httpMockHandler->appendFixtures([
            new Response(),
            new Response(),
        ]);

        $request = new Request('GET', 'http://example.com');

        $requestHeadersMiddleware->setHeader('foo', 'bar');
        $this->httpClient->send($request);

        $lastRequest = $this->httpHistoryContainer->getLastRequest();
        $this->assertEquals('bar', $lastRequest->getHeaderLine('foo'));

        $requestHeadersMiddleware->setHeader('foo', null);
        $this->httpClient->send($request);

        $lastRequest = $this->httpHistoryContainer->getLastRequest();
        $this->assertArrayNotHasKey('foo', $lastRequest->getHeaders());
    }

    /**
     * @dataProvider setBasicHttpAuthorizationDataProvider
     *
     * @param RequestInterface $request
     * @param HttpAuthenticationCredentials $httpAuthenticationCredentials
     * @param $expectedAuthorizationHeader
     *
     * @throws GuzzleException
     */
    public function testSetBasicHttpAuthorization(
        RequestInterface $request,
        HttpAuthenticationCredentials $httpAuthenticationCredentials,
        $expectedAuthorizationHeader
    ) {
        $httpAuthenticationMiddleware = self::$container->get(HttpAuthenticationMiddleware::class);

        $this->httpMockHandler->appendFixtures([
            new Response(),
            new Response(),
            new Response(),
        ]);

        $httpAuthenticationMiddleware->setHttpAuthenticationCredentials($httpAuthenticationCredentials);

        $this->httpClient->send($request);
        $this->httpClient->send($request);
        $this->httpClient->send($request);

        $historicalRequests = $this->httpHistoryContainer->getRequests();

        foreach ($historicalRequests as $historicalRequest) {
            $this->assertEquals($expectedAuthorizationHeader, $historicalRequest->getHeaderLine('authorization'));
        }
    }

    /**
     * @return array
     */
    public function setBasicHttpAuthorizationDataProvider()
    {
        return [
            'no username' => [
                'request' => new Request('GET', 'http://example.com'),
                'httpAuthenticationCredentials' => new HttpAuthenticationCredentials(
                    null,
                    null,
                    'example.com'
                ),
                'expectedAuthorizationHeader' => '',
            ],
            'has username, no password' => [
                'request' => new Request('GET', 'http://example.com'),
                'httpAuthenticationCredentials' => new HttpAuthenticationCredentials(
                    'foo',
                    null,
                    'example.com'
                ),
                'expectedAuthorizationHeader' => 'Basic Zm9vOg==',
            ],
            'host exactly matches domain' => [
                'request' => new Request('GET', 'http://example.com'),
                'httpAuthenticationCredentials' => new HttpAuthenticationCredentials(
                    'foo',
                    'bar',
                    'example.com'
                ),
                'expectedAuthorizationHeader' => 'Basic Zm9vOmJhcg==',
            ],
            'host exactly matches domain; given domain is uppercase' => [
                'request' => new Request('GET', 'http://example.com'),
                'httpAuthenticationCredentials' => new HttpAuthenticationCredentials(
                    'foo',
                    'bar',
                    'EXAMPLE.com'
                ),
                'expectedAuthorizationHeader' => 'Basic Zm9vOmJhcg==',
            ],
            'host ends with domain' => [
                'request' => new Request('GET', 'http://www.example.com'),
                'httpAuthenticationCredentials' => new HttpAuthenticationCredentials(
                    'foo',
                    'bar',
                    'example.com'
                ),
                'expectedAuthorizationHeader' => 'Basic Zm9vOmJhcg==',
            ],
        ];
    }
}
