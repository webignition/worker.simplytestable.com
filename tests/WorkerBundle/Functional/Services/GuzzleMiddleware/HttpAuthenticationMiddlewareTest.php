<?php

namespace Tests\WorkerBundle\Functional\Services\GuzzleMiddleware;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use SimplyTestable\WorkerBundle\Services\FooHttpClientService;
use Tests\WorkerBundle\Functional\AbstractBaseTestCase;

class HttpAuthenticationMiddlewareTest extends AbstractBaseTestCase
{
    /**
     * @dataProvider setAuthorizationHeaderOnRequestDataProvider
     *
     * @param RequestInterface $request
     * @param $username
     * @param $password
     * @param $domain
     * @param $expectedAuthorizationHeader
     *
     * @throws GuzzleException
     */
    public function testSetAuthorizationHeaderOnRequest(
        RequestInterface $request,
        $username,
        $password,
        $domain,
        $expectedAuthorizationHeader
    ) {
        $fooHttpClientService = $this->container->get(FooHttpClientService::class);

        $fooHttpClientService->appendFixtures([
            new Response(),
        ]);

        $httpClient = $fooHttpClientService->getHttpClient();
        $httpHistory = $fooHttpClientService->getHistory();

        $fooHttpClientService->setBasicHttpAuthorization($username, $password, $domain);

        $httpClient->send($request);

        $lastRequest = $httpHistory->getLastRequest();
        $this->assertEquals($expectedAuthorizationHeader, $lastRequest->getHeaderLine('authorization'));
    }

    /**
     * @return array
     */
    public function setAuthorizationHeaderOnRequestDataProvider()
    {
        return [
            'no username' => [
                'request' => new Request('GET', 'http://example.com'),
                'username' =>  null,
                'password' => null,
                'domain' => 'example.com',
                'expectedAuthorizationHeader' => '',
            ],
            'has username, no password' => [
                'request' => new Request('GET', 'http://example.com'),
                'username' =>  'foo',
                'password' => null,
                'domain' => 'example.com',
                'expectedAuthorizationHeader' => 'Basic Zm9vOg==',
            ],
            'host exactly matches domain' => [
                'request' => new Request('GET', 'http://example.com'),
                'username' =>  'foo',
                'password' => 'bar',
                'domain' => 'example.com',
                'expectedAuthorizationHeader' => 'Basic Zm9vOmJhcg==',
            ],
            'host exactly matches domain; given domain is uppercase' => [
                'request' => new Request('GET', 'http://example.com'),
                'username' =>  'foo',
                'password' => 'bar',
                'domain' => 'EXAMPLE.COM',
                'expectedAuthorizationHeader' => 'Basic Zm9vOmJhcg==',
            ],
            'host ends with domain' => [
                'request' => new Request('GET', 'http://www.example.com'),
                'username' =>  'foo',
                'password' => 'bar',
                'domain' => 'example.com',
                'expectedAuthorizationHeader' => 'Basic Zm9vOmJhcg==',
            ],
        ];
    }
}
