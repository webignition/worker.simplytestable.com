<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services\TaskTypePerformer;

use App\Services\TaskTypePerformer\TaskTypePerformerInterface;
use App\Tests\Services\TestTaskFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\HttpMockHandler;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;

abstract class AbstractWebPageTaskTypePerformerTest extends AbstractBaseTestCase
{
    /**
     * @var TestTaskFactory
     */
    protected $testTaskFactory;

    /**
     * @var HttpMockHandler
     */
    protected $httpMockHandler;

    /**
     * @var HttpHistoryContainer
     */
    protected $httpHistoryContainer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->testTaskFactory = self::$container->get(TestTaskFactory::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
        $this->httpHistoryContainer = self::$container->get(HttpHistoryContainer::class);
    }

    abstract protected function getTaskTypePerformer(): TaskTypePerformerInterface;
    abstract protected function getTaskTypeString():string;

    /**
     * @dataProvider cookiesDataProvider
     */
    abstract public function testSetCookiesOnRequests(array $taskParameters, string $expectedRequestCookieHeader);

    /**
     * @dataProvider httpAuthDataProvider
     */
    abstract public function testSetHttpAuthenticationOnRequests(
        array $taskParameters,
        string $expectedRequestAuthorizationHeaderValue
    );

    /**
     * @return array
     */
    public function cookiesDataProvider()
    {
        return [
            'no cookies' => [
                'taskParameters' => [],
                'expectedRequestCookieHeader' => '',
            ],
            'single cookie' => [
                'taskParameters' => [
                    'cookies' => [
                        [
                            'Name' => 'foo',
                            'Value' => 'bar',
                            'Domain' => '.example.com',
                        ],
                    ],
                ],
                'expectedRequestCookieHeader' => 'foo=bar',
            ],
            'multiple cookies' => [
                'taskParameters' => [
                    'cookies' => [
                        [
                            'Name' => 'foo1',
                            'Value' => 'bar1',
                            'Domain' => '.example.com',
                        ],
                        [
                            'Name' => 'foo2',
                            'Value' => 'bar2',
                            'Domain' => 'foo2.example.com',
                        ],
                        [
                            'Name' => 'foo3',
                            'Value' => 'bar3',
                            'Domain' => '.example.com',
                        ],
                    ],
                ],
                'expectedRequestCookieHeader' => 'foo1=bar1; foo3=bar3',
            ],
        ];
    }

    /**
     * @return array
     */
    public function httpAuthDataProvider()
    {
        return [
            'no auth' => [
                'taskParameters' => [],
                'expectedRequestAuthorizationHeaderValue' => '',
            ],
            'has auth' => [
                'taskParameters' => [
                    'http-auth-username' => 'foouser',
                    'http-auth-password' => 'foopassword',
                ],
                'expectedRequestAuthorizationHeaderValue' => 'foouser:foopassword',
            ],
        ];
    }

    protected function assertPostConditions()
    {
        parent::assertPostConditions();

        $this->assertEquals(0, $this->httpMockHandler->count());
    }
}
