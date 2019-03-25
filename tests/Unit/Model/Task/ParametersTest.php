<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Unit\Model\Task;

use App\Model\Task\Parameters;

class ParametersTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getCookiesDataProvider
     */
    public function testGetCookies(array $taskParametersArray, array $expectedCookieStrings)
    {
        $parametersObject = new Parameters($taskParametersArray, '');

        $cookies = $parametersObject->getCookies();

        $this->assertEquals(count($expectedCookieStrings), count($cookies));

        foreach ($cookies as $cookieIndex => $cookie) {
            $this->assertEquals($expectedCookieStrings[$cookieIndex], (string)$cookie);
        }
    }

    public function getCookiesDataProvider(): array
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
     */
    public function testGetHttpAuthenticationCredentials(
        array $taskParametersArray,
        string $expectedHttpAuthenticationUsername,
        string $expectedHttpAuthenticationPassword
    ) {
        $parametersObject = new Parameters($taskParametersArray, 'http://example.com/');

        $this->assertEquals($expectedHttpAuthenticationUsername, $parametersObject->getHttpAuthenticationUsername());
        $this->assertEquals($expectedHttpAuthenticationPassword, $parametersObject->getHttpAuthenticationPassword());
    }

    public function getHttpAuthenticationCredentialsDataProvider(): array
    {
        return [
            'no parameters' => [
                'taskParametersArray' => [],
                'expectedHttpAuthenticationUsername' => '',
                'expectedHttpAuthenticationPassword' => '',
            ],
            'http auth parameters; no username, has password' => [
                'taskParametersArray' => [
                    'http-auth-password' => 'password value',
                ],
                'expectedHttpAuthenticationUsername' => '',
                'expectedHttpAuthenticationPassword' => 'password value',
            ],
            'http auth parameters; has username, no password' => [
                'taskParametersArray' => [
                    'http-auth-username' => 'username value',
                ],
                'expectedHttpAuthenticationUsername' => 'username value',
                'expectedHttpAuthenticationPassword' => '',
            ],
            'http auth parameters; has username, has password' => [
                'taskParametersArray' => [
                    'http-auth-username' => 'username value',
                    'http-auth-password' => 'password value',
                ],
                'expectedHttpAuthenticationUsername' => 'username value',
                'expectedHttpAuthenticationPassword' => 'password value',
            ],
        ];
    }

    public function testGet()
    {
        $parameters = new Parameters([
            'foo' => 'bar',
        ], '');

        $this->assertNull($parameters->get('fizz'));
        $this->assertEquals('bar', $parameters->get('foo'));
    }
}
