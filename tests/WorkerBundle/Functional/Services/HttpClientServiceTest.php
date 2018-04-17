<?php

namespace Tests\WorkerBundle\Functional\Services;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use SimplyTestable\WorkerBundle\Model\HttpAuthenticationCredentials;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use Tests\WorkerBundle\Functional\AbstractBaseTestCase;
use Tests\WorkerBundle\Services\TestHttpClientService;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

class HttpClientServiceTest extends AbstractBaseTestCase
{
    /**
     * @var TestHttpClientService
     */
    private $fooHttpClientService;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * @var HttpHistoryContainer
     */
    private $httpHistory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->fooHttpClientService = $this->container->get(HttpClientService::class);
        $this->httpClient = $this->fooHttpClientService->getHttpClient();
        $this->httpHistory = $this->fooHttpClientService->getHistory();
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
        $this->fooHttpClientService->appendFixtures([
            new Response(),
            new Response(),
            new Response(),
        ]);

        $this->fooHttpClientService->setRequestHeader($name, $value);

        $request = new Request('GET', 'http://example.com');

        $this->httpClient->send($request);
        $this->httpClient->send($request);
        $this->httpClient->send($request);

        $historicalRequests = $this->httpHistory->getRequests();

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
        $this->fooHttpClientService->appendFixtures([
            new Response(),
            new Response(),
        ]);

        $request = new Request('GET', 'http://example.com');

        $this->fooHttpClientService->setRequestHeader('foo', 'bar');
        $this->httpClient->send($request);

        $lastRequest = $this->httpHistory->getLastRequest();
        $this->assertEquals('bar', $lastRequest->getHeaderLine('foo'));

        $this->fooHttpClientService->setRequestHeader('foo', null);
        $this->httpClient->send($request);

        $lastRequest = $this->httpHistory->getLastRequest();
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
        $this->fooHttpClientService->appendFixtures([
            new Response(),
            new Response(),
            new Response(),
        ]);

        $this->fooHttpClientService->setBasicHttpAuthorization($httpAuthenticationCredentials);

        $this->httpClient->send($request);
        $this->httpClient->send($request);
        $this->httpClient->send($request);

        $historicalRequests = $this->httpHistory->getRequests();

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
