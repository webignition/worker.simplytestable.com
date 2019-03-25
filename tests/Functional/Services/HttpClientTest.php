<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use App\Services\HttpClientService;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\HttpMockHandler;
use Psr\Http\Message\ResponseInterface;
use webignition\Guzzle\Middleware\HttpAuthentication\AuthorizationType;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationMiddleware;
use webignition\Guzzle\Middleware\RequestHeaders\RequestHeadersMiddleware;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class HttpClientTest extends AbstractBaseTestCase
{
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

    protected function setUp()
    {
        parent::setUp();

        $httpClientService = self::$container->get(HttpClientService::class);
        $this->httpClient = $httpClientService->getHttpClient();
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
        $this->httpHistoryContainer = self::$container->get(HttpHistoryContainer::class);
    }

    /**
     * @dataProvider setHeaderDataProvider
     */
    public function testRequestHeadersMiddlewareSetHeader(string $name, ?string $value)
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

        /* @var RequestInterface[] $historicalRequests */
        $historicalRequests = $this->httpHistoryContainer->getRequests();

        foreach ($historicalRequests as $historicalRequest) {
            $this->assertEquals($value, $historicalRequest->getHeaderLine($name));
        }
    }

    public function setHeaderDataProvider(): array
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

    public function testRequestHeadersMiddlewareClearHeader()
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
        if ($lastRequest instanceof RequestInterface) {
            $this->assertEquals('bar', $lastRequest->getHeaderLine('foo'));
        }

        $requestHeadersMiddleware->setHeader('foo', null);
        $this->httpClient->send($request);

        $lastRequest = $this->httpHistoryContainer->getLastRequest();
        if ($lastRequest instanceof RequestInterface) {
            $this->assertArrayNotHasKey('foo', $lastRequest->getHeaders());
        }
    }

    /**
     * @dataProvider setBasicHttpAuthorizationDataProvider
     */
    public function testHttpAuthenticationMiddleware(
        RequestInterface $request,
        string $host,
        string $credentials,
        string $expectedAuthorizationHeader
    ) {
        /* @var HttpAuthenticationMiddleware $httpAuthenticationMiddleware */
        $httpAuthenticationMiddleware = self::$container->get(HttpAuthenticationMiddleware::class);

        $this->httpMockHandler->appendFixtures([
            new Response(),
            new Response(),
            new Response(),
        ]);

        $httpAuthenticationMiddleware->setType(AuthorizationType::BASIC);
        $httpAuthenticationMiddleware->setHost($host);
        $httpAuthenticationMiddleware->setCredentials($credentials);

        $this->httpClient->send($request);
        $this->httpClient->send($request);
        $this->httpClient->send($request);

        /* @var RequestInterface[] $historicalRequests */
        $historicalRequests = $this->httpHistoryContainer->getRequests();

        foreach ($historicalRequests as $historicalRequest) {
            $this->assertEquals($expectedAuthorizationHeader, $historicalRequest->getHeaderLine('authorization'));
        }
    }

    public function setBasicHttpAuthorizationDataProvider(): array
    {
        return [
            'empty credentials' => [
                'request' => new Request('GET', 'http://example.com'),
                'host' => 'example.com',
                'credentials' => '',
                'expectedAuthorizationHeader' => '',
            ],
            'non-empty credentials; host exactly matches domain' => [
                'request' => new Request('GET', 'http://example.com'),
                'host' => 'example.com',
                'credentials' => 'Zm9vOg==',
                'expectedAuthorizationHeader' => 'Basic Zm9vOg==',
            ],
            'non-empty credentials; host exactly matches domain, domain is uppercase' => [
                'request' => new Request('GET', 'http://example.com'),
                'host' => 'EXAMPLE.com',
                'credentials' => 'Zm9vOg==',
                'expectedAuthorizationHeader' => 'Basic Zm9vOg==',
            ],
            'host ends with domain' => [
                'request' => new Request('GET', 'http://foo.example.com'),
                'host' => 'example.com',
                'credentials' => 'Zm9vOg==',
                'expectedAuthorizationHeader' => 'Basic Zm9vOg==',
            ],
        ];
    }

    public function testResponseUriFixerMiddleware()
    {
        $this->httpMockHandler->appendFixtures([
            new Response(302, [
                'Location' => 'https:///example.com/',
            ]),
            new Response(200),
        ]);

        $response = $this->httpClient->get('http://example.com/');

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }
}
