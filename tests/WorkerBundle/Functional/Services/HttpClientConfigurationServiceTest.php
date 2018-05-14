<?php

namespace Tests\WorkerBundle\Functional\Services;

use GuzzleHttp\Cookie\SetCookie;
use Mockery\Mock;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Services\HttpClientConfigurationService;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use Tests\WorkerBundle\Functional\AbstractBaseTestCase;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationCredentials;

class HttpClientConfigurationServiceTest extends AbstractBaseTestCase
{
    public function testGetFromContainer()
    {
        $this->assertInstanceOf(
            HttpClientConfigurationService::class,
            $this->container->get(HttpClientConfigurationService::class)
        );
    }

    public function testConfigureForTaskNoCookiesNoHttpAuthentication()
    {
        $task = $this->createTask([], 'http://example.com/');
        $userAgentString = 'Foo User Agent';

        /* @var HttpClientService|Mock $httpClientService */
        $httpClientService = \Mockery::mock(HttpClientService::class);

        $httpClientService
            ->shouldReceive('setRequestHeader')
            ->with('User-Agent', $userAgentString);

        $httpClientConfigurationService = new HttpClientConfigurationService($httpClientService);

        $httpClientConfigurationService->configureForTask($task, $userAgentString);
    }

    /**
     * @dataProvider configureForTaskHasCookiesDataProvider
     *
     * @param array $taskParameters
     * @param array $expectedCookieStrings
     */
    public function testConfigureForTaskHasCookies(array $taskParameters, array $expectedCookieStrings)
    {
        $task = $this->createTask($taskParameters, 'http://example.com/');
        $userAgentString = 'Foo User Agent';

        /* @var HttpClientService|Mock $httpClientService */
        $httpClientService = \Mockery::mock(HttpClientService::class);

        $httpClientService
            ->shouldReceive('setCookies')
            ->withArgs(function (array $cookies) use ($expectedCookieStrings) {
                $cookieStrings = [];

                /* @var SetCookie $cookie */
                foreach ($cookies as $cookie) {
                    $cookieStrings[] = (string)$cookie;
                }

                $this->assertEquals($expectedCookieStrings, $cookieStrings);

                return true;
            });

        $httpClientService
            ->shouldReceive('setRequestHeader')
            ->with('User-Agent', $userAgentString);

        $httpClientConfigurationService = new HttpClientConfigurationService($httpClientService);

        $httpClientConfigurationService->configureForTask($task, $userAgentString);
    }

    /**
     * @return array
     */
    public function configureForTaskHasCookiesDataProvider()
    {
        return [
            'single cookie' => [
                'taskParameters' => [
                    'cookies' => [
                        [
                            'name' => 'cookie-name',
                            'value' => 'cookie-value',
                            'domain' => 'example.com',
                        ],
                    ],
                ],
                'expectedCookieStrings' => [
                    'cookie-name=cookie-value; Domain=example.com; Path=/',
                ],
            ],
            'two cookies' => [
                'taskParameters' => [
                    'cookies' => [
                        [
                            'name' => 'cookie-0-name',
                            'value' => 'cookie-0-value',
                            'domain' => 'example.com',
                        ],
                        [
                            'name' => 'cookie-1-name',
                            'value' => 'cookie-1-value',
                            'domain' => 'example.com',
                        ],
                    ],
                ],
                'expectedCookieStrings' => [
                    'cookie-0-name=cookie-0-value; Domain=example.com; Path=/',
                    'cookie-1-name=cookie-1-value; Domain=example.com; Path=/',
                ],
            ],
        ];
    }

    /**
     * @dataProvider configureForTaskHasHttpAuthenticationCredentialsDataProvider
     *
     * @param array $taskParameters
     * @param array $expectedHttpAuthenticationCredentials
     */
    public function testConfigureForTaskHasHttpAuthenticationCredentials(
        array $taskParameters,
        array $expectedHttpAuthenticationCredentials
    ) {
        $task = $this->createTask($taskParameters, 'http://example.com/');
        $userAgentString = 'Foo User Agent';

        /* @var HttpClientService|Mock $httpClientService */
        $httpClientService = \Mockery::mock(HttpClientService::class);

        $httpClientService
            ->shouldReceive('setBasicHttpAuthorization')
            ->withArgs(function (
                HttpAuthenticationCredentials $httpAuthenticationCredentials
            ) use ($expectedHttpAuthenticationCredentials) {
                $this->assertEquals(
                    $expectedHttpAuthenticationCredentials['username'],
                    $httpAuthenticationCredentials->getUsername()
                );

                $this->assertEquals(
                    $expectedHttpAuthenticationCredentials['password'],
                    $httpAuthenticationCredentials->getPassword()
                );

                $this->assertEquals('example.com', $httpAuthenticationCredentials->getDomain());

                return true;
            });

        $httpClientService
            ->shouldReceive('setRequestHeader')
            ->with('User-Agent', $userAgentString);

        $httpClientConfigurationService = new HttpClientConfigurationService($httpClientService);

        $httpClientConfigurationService->configureForTask($task, $userAgentString);
    }

    /**
     * @return array
     */
    public function configureForTaskHasHttpAuthenticationCredentialsDataProvider()
    {
        return [
            'has username, no password' => [
                'taskParameters' => [
                    'http-auth-username' => 'username value',
                ],
                'expectedHttpAuthenticationCredentials' => [
                    'username' => 'username value',
                    'password' => '',
                    'domain' => 'example.com',
                ],
            ],
            'has username, has password' => [
                'taskParameters' => [
                    'http-auth-username' => 'username value',
                    'http-auth-password' => 'password value',
                ],
                'expectedHttpAuthenticationCredentials' => [
                    'username' => 'username value',
                    'password' => 'password value',
                    'domain' => 'example.com',
                ],
            ],
        ];
    }

    /**
     * @param array $parametersArray
     * @param string $url
     *
     * @return Task
     */
    private function createTask(array $parametersArray, $url = '')
    {
        $task = new Task();
        $task->setUrl($url);
        $task->setParameters(json_encode($parametersArray));

        return $task;
    }
}
