<?php

namespace Tests\WorkerBundle\Functional\Services;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use Tests\WorkerBundle\Functional\AbstractBaseTestCase;
use Tests\WorkerBundle\Services\HttpMockHandler;
use Tests\WorkerBundle\Services\TestHttpClientService;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationCredentials;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class HttpClientServiceTest extends AbstractBaseTestCase
{
    /**
     * @var TestHttpClientService
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
        $this->httpMockHandler->appendFixtures([
            new Response(),
            new Response(),
            new Response(),
        ]);

        $this->httpClientService->setRequestHeader($name, $value);

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
        $this->httpMockHandler->appendFixtures([
            new Response(),
            new Response(),
        ]);

        $request = new Request('GET', 'http://example.com');

        $this->httpClientService->setRequestHeader('foo', 'bar');
        $this->httpClient->send($request);

        $lastRequest = $this->httpHistoryContainer->getLastRequest();
        $this->assertEquals('bar', $lastRequest->getHeaderLine('foo'));

        $this->httpClientService->setRequestHeader('foo', null);
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
        $this->httpMockHandler->appendFixtures([
            new Response(),
            new Response(),
            new Response(),
        ]);

        $this->httpClientService->setBasicHttpAuthorization($httpAuthenticationCredentials);

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
