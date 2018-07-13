<?php

namespace Tests\AppBundle\Unit\Model\Task;

use Mockery\Mock;
use SimplyTestable\AppBundle\Entity\Task\Task;
use SimplyTestable\AppBundle\Model\Task\Parameters;
use webignition\Guzzle\Middleware\HttpAuthentication\HttpAuthenticationCredentials;

class ParametersTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getCookiesDataProvider
     *
     * @param array $taskParametersArray
     * @param array $expectedCookieStrings
     */
    public function testGetCookies(array $taskParametersArray, array $expectedCookieStrings)
    {
        $task = $this->createTask($taskParametersArray);
        $parametersObject = new Parameters($task);

        $cookies = $parametersObject->getCookies();

        $this->assertEquals(count($expectedCookieStrings), count($cookies));

        foreach ($cookies as $cookieIndex => $cookie) {
            $this->assertEquals($expectedCookieStrings[$cookieIndex], (string)$cookie);
        }
    }

    /**
     * @return array
     */
    public function getCookiesDataProvider()
    {
        return [
            'no parameters' => [
                'taskParametersArray' => [],
                'expectedCookieStrings' => [],
            ],
            'empty cookies in parameters' => [
                'taskParametersArray' => [
                    'cookies' => [],
                ],
                'expectedCookieStrings' => [],
            ],
            'has cookies; lowercase keys' => [
                'taskParametersArray' => [
                    'cookies' => [
                        [
                            'name' => 'cookie-0',
                            'value' => 'value-0',
                            'domain' => 'foo',
                        ],
                        [
                            'name' => 'cookie-1',
                            'value' => 'value-1',
                            'domain' => 'foo',
                        ],
                    ],
                ],
                'expectedCookieStrings' => [
                    'cookie-0=value-0; Domain=foo; Path=/',
                    'cookie-1=value-1; Domain=foo; Path=/',
                ],
            ],
            'has cookies; uppercase keys' => [
                'taskParametersArray' => [
                    'cookies' => [
                        [
                            'NAME' => 'cookie-0',
                            'VALUE' => 'value-0',
                            'DOMAIN' => 'foo',
                        ],
                        [
                            'NAME' => 'cookie-1',
                            'VALUE' => 'value-1',
                            'DOMAIN' => 'foo',
                        ],
                    ],
                ],
                'expectedCookieStrings' => [
                    'cookie-0=value-0; Domain=foo; Path=/',
                    'cookie-1=value-1; Domain=foo; Path=/',
                ],
            ],
            'has cookies; ucfirst keys' => [
                'taskParametersArray' => [
                    'cookies' => [
                        [
                            'Name' => 'cookie-0',
                            'Value' => 'value-0',
                            'Domain' => 'foo',
                        ],
                        [
                            'Name' => 'cookie-1',
                            'Value' => 'value-1',
                            'Domain' => 'foo',
                        ],
                    ],
                ],
                'expectedCookieStrings' => [
                    'cookie-0=value-0; Domain=foo; Path=/',
                    'cookie-1=value-1; Domain=foo; Path=/',
                ],
            ],
        ];
    }

    /**
     * @dataProvider getHttpAuthenticationCredentialsDataProvider
     *
     * @param string $taskUrl
     * @param array $taskParametersArray
     * @param array $expectedHttpAuthenticationCredentialsValues
     */
    public function testGetHttpAuthenticationCredentials(
        $taskUrl,
        array $taskParametersArray,
        array $expectedHttpAuthenticationCredentialsValues
    ) {
        $task = $this->createTask($taskParametersArray, $taskUrl);
        $parametersObject = new Parameters($task);

        $httpAuthenticationCredentials = $parametersObject->getHttpAuthenticationCredentials();

        $this->assertInstanceOf(HttpAuthenticationCredentials::class, $httpAuthenticationCredentials);

        $this->assertEquals(
            $expectedHttpAuthenticationCredentialsValues['username'],
            $httpAuthenticationCredentials->getUsername()
        );

        $this->assertEquals(
            $expectedHttpAuthenticationCredentialsValues['password'],
            $httpAuthenticationCredentials->getPassword()
        );

        $this->assertEquals(
            $expectedHttpAuthenticationCredentialsValues['domain'],
            $httpAuthenticationCredentials->getDomain()
        );
    }

    /**
     * @return array
     */
    public function getHttpAuthenticationCredentialsDataProvider()
    {
        return [
            'no parameters' => [
                'taskUrl' => 'http://example.com/',
                'taskParametersArray' => [],
                'expectedHttpAuthenticationCredentialsValues' => [
                    'username' => '',
                    'password' => '',
                    'domain' => '',
                ],
            ],
            'http auth parameters; no username, has password' => [
                'taskUrl' => 'http://example.com/',
                'taskParametersArray' => [
                    'http-auth-password' => 'password value',
                ],
                'expectedHttpAuthenticationCredentialsValues' => [
                    'username' => '',
                    'password' => '',
                    'domain' => '',
                ],
            ],
            'http auth parameters; has username, no password' => [
                'taskUrl' => 'http://example.com/',
                'taskParametersArray' => [
                    'http-auth-username' => 'username value',
                ],
                'expectedHttpAuthenticationCredentialsValues' => [
                    'username' => 'username value',
                    'password' => '',
                    'domain' => 'example.com',
                ],
            ],
            'http auth parameters; has username, has password' => [
                'taskUrl' => 'http://example.com/',
                'taskParametersArray' => [
                    'http-auth-username' => 'username value',
                    'http-auth-password' => 'password value',
                ],
                'expectedHttpAuthenticationCredentialsValues' => [
                    'username' => 'username value',
                    'password' => 'password value',
                    'domain' => 'example.com',
                ],
            ],
            'http auth parameters; different domain' => [
                'taskUrl' => 'http://example.org/',
                'taskParametersArray' => [
                    'http-auth-username' => 'username value',
                    'http-auth-password' => 'password value',
                ],
                'expectedHttpAuthenticationCredentialsValues' => [
                    'username' => 'username value',
                    'password' => 'password value',
                    'domain' => 'example.org',
                ],
            ],
            'http auth parameters; different domain, subdomain' => [
                'taskUrl' => 'http://foo.example.org/',
                'taskParametersArray' => [
                    'http-auth-username' => 'username value',
                    'http-auth-password' => 'password value',
                ],
                'expectedHttpAuthenticationCredentialsValues' => [
                    'username' => 'username value',
                    'password' => 'password value',
                    'domain' => 'foo.example.org',
                ],
            ],
        ];
    }

    /**
     * @param array $taskParametersArray
     * @param string $url
     *
     * @return Mock|Task
     */
    private function createTask(array $taskParametersArray, $url = '')
    {
        /* @var Mock|Task $task */
        $task = \Mockery::mock(Task::class);
        $task
            ->shouldReceive('getParametersArray')
            ->andReturn($taskParametersArray);

        if (!empty($url)) {
            $task
                ->shouldReceive('getUrl')
                ->andReturn($url);
        }

        return $task;
    }
}
