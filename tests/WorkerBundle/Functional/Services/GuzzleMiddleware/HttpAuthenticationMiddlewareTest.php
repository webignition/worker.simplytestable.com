<?php

namespace Tests\WorkerBundle\Functional\Services\GuzzleMiddleware;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use SimplyTestable\WorkerBundle\Services\GuzzleMiddleware\HttpAuthenticationMiddleware;
use Tests\WorkerBundle\Functional\AbstractBaseTestCase;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

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
        $httpHistoryContainer = $this->container->get(HttpHistoryContainer::class);

        $mockHandler = new MockHandler([
            new Response(),
        ]);

        $httpAuthenticationMiddleware = new HttpAuthenticationMiddleware();
        $httpAuthenticationMiddleware->setUsername($username);
        $httpAuthenticationMiddleware->setPassword($password);
        $httpAuthenticationMiddleware->setDomain($domain);

        $handlerStack = HandlerStack::create($mockHandler);
        $handlerStack->push($httpAuthenticationMiddleware, 'http_auth');
        $handlerStack->push(Middleware::history($httpHistoryContainer), 'history');

        $client = new Client(['handler' => $handlerStack]);
        $client->send($request);

        $lastRequest = $httpHistoryContainer->getLastRequest();
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
