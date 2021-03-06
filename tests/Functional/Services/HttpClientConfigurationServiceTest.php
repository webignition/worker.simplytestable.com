<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services;

use App\Model\Task\TypeInterface;
use App\Tests\Services\TaskTypeRetriever;
use GuzzleHttp\Cookie\CookieJarInterface;
use Mockery\Mock;
use App\Entity\Task\Task;
use App\Services\HttpClientConfigurationService;
use App\Tests\Functional\AbstractBaseTestCase;
use webignition\Guzzle\Middleware\HttpAuthentication\AuthorizationType;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationMiddleware;
use webignition\Guzzle\Middleware\RequestHeaders\RequestHeadersMiddleware;

class HttpClientConfigurationServiceTest extends AbstractBaseTestCase
{
    /**
     * @var TypeInterface
     */
    private $htmlValidationTaskType;

    protected function setUp()
    {
        parent::setUp();

        $taskTypeRetriever = self::$container->get(TaskTypeRetriever::class);
        $this->htmlValidationTaskType = $taskTypeRetriever->retrieve(TypeInterface::TYPE_HTML_VALIDATION);
    }

    public function testGetFromContainer()
    {
        $this->assertInstanceOf(
            HttpClientConfigurationService::class,
            self::$container->get(HttpClientConfigurationService::class)
        );
    }

    public function testConfigureForTaskNoCookiesNoHttpAuthentication()
    {
        $task = $this->createTask();
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
     */
    public function testConfigureForTaskHasCookies(array $taskParameters, array $expectedCookieStrings)
    {
        $task = $this->createTask($taskParameters);

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

    public function configureForTaskHasCookiesDataProvider(): array
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
     */
    public function testConfigureForTaskHasHttpAuthenticationCredentials(
        array $taskParameters,
        string $expectedHost,
        string $expectedCredentials
    ) {
        $task = $this->createTask($taskParameters);
        $userAgentString = 'Foo User Agent';

        /* @var HttpAuthenticationMiddleware|Mock $httpAuthenticationMiddleware */
        $httpAuthenticationMiddleware = \Mockery::mock(HttpAuthenticationMiddleware::class);

        $httpAuthenticationMiddleware
            ->shouldReceive('setType')
            ->with(AuthorizationType::BASIC);

        $httpAuthenticationMiddleware
            ->shouldReceive('setHost')
            ->with($expectedHost);

        $httpAuthenticationMiddleware
            ->shouldReceive('setCredentials')
            ->with($expectedCredentials);

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

    public function configureForTaskHasHttpAuthenticationCredentialsDataProvider(): array
    {
        return [
            'has username, no password' => [
                'taskParameters' => [
                    'http-auth-username' => 'username value',
                ],
                'expectedHost' => 'example.com',
                'expectedCredentials' => 'dXNlcm5hbWUgdmFsdWU6'
            ],
            'has username, has password' => [
                'taskParameters' => [
                    'http-auth-username' => 'username value',
                    'http-auth-password' => 'password value',
                ],
                'expectedHost' => 'example.com',
                'expectedCredentials' => 'dXNlcm5hbWUgdmFsdWU6cGFzc3dvcmQgdmFsdWU='
            ],
        ];
    }

    private function createTask(array $parameters = []): Task
    {
        $taskTypeRetriever = self::$container->get(TaskTypeRetriever::class);

        return Task::create(
            $taskTypeRetriever->retrieve(TypeInterface::TYPE_HTML_VALIDATION),
            'http://example.com/',
            (string) json_encode($parameters)
        );
    }
}
