<?php

namespace App\Tests\Functional\Services;

use App\Model\Task\Type;
use App\Services\TaskTypeService;
use GuzzleHttp\Cookie\CookieJarInterface;
use Mockery\Mock;
use App\Entity\Task\Task;
use App\Services\HttpClientConfigurationService;
use App\Tests\Functional\AbstractBaseTestCase;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationCredentials;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationMiddleware;
use webignition\Guzzle\Middleware\RequestHeaders\RequestHeadersMiddleware;

class HttpClientConfigurationServiceTest extends AbstractBaseTestCase
{
    public function testGetFromContainer()
    {
        $this->assertInstanceOf(
            HttpClientConfigurationService::class,
            self::$container->get(HttpClientConfigurationService::class)
        );
    }

    public function testConfigureForTaskNoCookiesNoHttpAuthentication()
    {
        $taskType = self::$container->get(TaskTypeService::class)->get(Type::TYPE_HTML_VALIDATION);

        $task = Task::create($taskType, 'http://example.com/');
        $userAgentString = 'Foo User Agent';

        /* @var HttpAuthenticationMiddleware|Mock $httpAuthenticationMiddleware */
        $httpAuthenticationMiddleware = \Mockery::mock(HttpAuthenticationMiddleware::class);
        $httpAuthenticationMiddleware
            ->shouldNotReceive('setHttpAuthenticationCredentials');

        /* @var RequestHeadersMiddleware|Mock $requestHeadersMiddleware */
        $requestHeadersMiddleware = \Mockery::mock(RequestHeadersMiddleware::class);
        $requestHeadersMiddleware
            ->shouldReceive('setHeader')
            ->with('User-Agent', $userAgentString)
            ->once();

        /* @var CookieJarInterface|Mock $cookieJar */
        $cookieJar = \Mockery::mock(CookieJarInterface::class);
        $cookieJar
            ->shouldNotReceive('clear');
        $cookieJar
            ->shouldNotReceive('setCookie');

        $httpClientConfigurationService = new HttpClientConfigurationService(
            $httpAuthenticationMiddleware,
            $requestHeadersMiddleware,
            $cookieJar
        );

        $httpClientConfigurationService->configureForTask($task, $userAgentString);

        $this->addToAssertionCount(\Mockery::getContainer()->mockery_getExpectationCount());
    }

    /**
     * @dataProvider configureForTaskHasCookiesDataProvider
     *
     * @param array $taskParameters
     * @param array $expectedCookieStrings
     */
    public function testConfigureForTaskHasCookies(array $taskParameters, array $expectedCookieStrings)
    {
        $taskType = self::$container->get(TaskTypeService::class)->get(Type::TYPE_HTML_VALIDATION);

        $task = Task::create($taskType, 'http://example.com/', json_encode($taskParameters));

        $userAgentString = 'Foo User Agent';

        /* @var HttpAuthenticationMiddleware|Mock $httpAuthenticationMiddleware */
        $httpAuthenticationMiddleware = \Mockery::mock(HttpAuthenticationMiddleware::class);
        $httpAuthenticationMiddleware
            ->shouldNotReceive('setHttpAuthenticationCredentials');

        /* @var RequestHeadersMiddleware|Mock $requestHeadersMiddleware */
        $requestHeadersMiddleware = \Mockery::mock(RequestHeadersMiddleware::class);
        $requestHeadersMiddleware
            ->shouldReceive('setHeader')
            ->with('User-Agent', $userAgentString)
            ->once();

        /* @var CookieJarInterface|Mock $cookieJar */
        $cookieJar = \Mockery::mock(CookieJarInterface::class);
        $cookieJar
            ->shouldReceive('clear')
            ->once();

        $cookieJar
            ->shouldReceive('setCookie')
            ->times(count($expectedCookieStrings));

        $httpClientConfigurationService = new HttpClientConfigurationService(
            $httpAuthenticationMiddleware,
            $requestHeadersMiddleware,
            $cookieJar
        );

        $this->addToAssertionCount(\Mockery::getContainer()->mockery_getExpectationCount());

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
        $taskType = self::$container->get(TaskTypeService::class)->get(Type::TYPE_HTML_VALIDATION);

        $task = Task::create($taskType, 'http://example.com/', json_encode($taskParameters));
        $userAgentString = 'Foo User Agent';

        /* @var HttpAuthenticationMiddleware|Mock $httpAuthenticationMiddleware */
        $httpAuthenticationMiddleware = \Mockery::mock(HttpAuthenticationMiddleware::class);
        $httpAuthenticationMiddleware
            ->shouldReceive('setHttpAuthenticationCredentials')
            ->once()
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

                $this->assertEquals(
                    $expectedHttpAuthenticationCredentials['domain'],
                    $httpAuthenticationCredentials->getDomain()
                );

                return true;
            });

        /* @var RequestHeadersMiddleware|Mock $requestHeadersMiddleware */
        $requestHeadersMiddleware = \Mockery::mock(RequestHeadersMiddleware::class);
        $requestHeadersMiddleware
            ->shouldReceive('setHeader')
            ->with('User-Agent', $userAgentString)
            ->once();

        /* @var CookieJarInterface|Mock $cookieJar */
        $cookieJar = \Mockery::mock(CookieJarInterface::class);
        $cookieJar
            ->shouldNotReceive('clear');
        $cookieJar
            ->shouldNotReceive('setCookie');

        $httpClientConfigurationService = new HttpClientConfigurationService(
            $httpAuthenticationMiddleware,
            $requestHeadersMiddleware,
            $cookieJar
        );

        $httpClientConfigurationService->configureForTask($task, $userAgentString);

        $this->addToAssertionCount(\Mockery::getContainer()->mockery_getExpectationCount());
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
}
