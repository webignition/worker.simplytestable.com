<?php

namespace Tests\WorkerBundle\Functional\Services\TaskDriver;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Message\RequestInterface;
use Mockery\MockInterface;
use SimplyTestable\WorkerBundle\Services\TaskDriver\TaskDriver;
use Tests\WorkerBundle\Functional\AbstractBaseTestCase;
use Tests\WorkerBundle\Factory\ConnectExceptionFactory;
use Tests\WorkerBundle\Factory\TestTaskFactory;
use webignition\WebResource\Service\Configuration;
use webignition\WebResource\Service\Service as WebResourceService;

abstract class WebResourceTaskDriverTest extends AbstractBaseTestCase
{
    /**
     * @var TestTaskFactory
     */
    protected $testTaskFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->testTaskFactory = new TestTaskFactory($this->container);
    }

    /**
     * @return TaskDriver
     */
    abstract protected function getTaskDriver();

    /**
     * @return string
     */
    abstract protected function getTaskTypeString();

    /**
     * @dataProvider cookiesDataProvider
     *
     * @param array $taskParameters
     * @param string $expectedRequestCookieHeader
     */
    abstract public function testSetCookiesOnHttpClient($taskParameters, $expectedRequestCookieHeader);

    /**
     * @dataProvider httpAuthDataProvider
     *
     * @param array $taskParameters
     * @param string $expectedRequestAuthorizationHeaderValue
     */
    abstract public function testSetHttpAuthOnHttpClient($taskParameters, $expectedRequestAuthorizationHeaderValue);

    public function testPerformNonCurlConnectException()
    {
        /* @var $request MockInterface|RequestInterface */
        $request = \Mockery::mock(RequestInterface::class);

        $this->setHttpFixtures([
            new ConnectException('foo', $request)
        ]);

        $task = $this->testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults()
        );

        $this->expectException(ConnectException::class);
        $this->expectExceptionMessage('foo');
        $this->expectExceptionCode(0);

        $this->getTaskDriver()->perform($task);
    }

    /**
     * @dataProvider performBadWebResourceDataProvider
     *
     * @param string[] $httpResponseFixtures
     * @param bool $expectedWebResourceRetrievalHasSucceeded
     * @param bool $expectedIsRetryable
     * @param int $expectedErrorCount
     * @param string $expectedTaskOutput
     */
    public function testPerformBadWebResource(
        $httpResponseFixtures,
        $expectedWebResourceRetrievalHasSucceeded,
        $expectedIsRetryable,
        $expectedErrorCount,
        $expectedTaskOutput
    ) {
        $this->setHttpFixtures($httpResponseFixtures);
        $webResourceService = $this->container->get(WebResourceService::class);

        $webResourceServiceConfiguration = $webResourceService->getConfiguration();
        $newWebResourceServiceConfiguration = $webResourceServiceConfiguration->createFromCurrent([
            Configuration::CONFIG_RETRY_WITH_URL_ENCODING_DISABLED => false,
        ]);

        $webResourceService->setConfiguration($newWebResourceServiceConfiguration);

        $task = $this->testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults()
        );

        $taskDriver = $this->getTaskDriver();

        $taskDriverResponse = $taskDriver->perform($task);

        $this->assertEquals($expectedWebResourceRetrievalHasSucceeded, $taskDriverResponse->hasSucceeded());
        $this->assertEquals($expectedIsRetryable, $taskDriverResponse->isRetryable());
        $this->assertEquals($expectedErrorCount, $taskDriverResponse->getErrorCount());

        $this->assertEquals(
            $expectedTaskOutput,
            json_decode($taskDriverResponse->getTaskOutput()->getOutput(), true)
        );
    }

    public function performBadWebResourceDataProvider()
    {
        return [
            'http too many redirects' => [
                'httpResponseFixtures' => [
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TestTaskFactory::DEFAULT_TASK_URL . "1",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TestTaskFactory::DEFAULT_TASK_URL . "2",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TestTaskFactory::DEFAULT_TASK_URL . "3",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TestTaskFactory::DEFAULT_TASK_URL . "4",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TestTaskFactory::DEFAULT_TASK_URL . "5",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TestTaskFactory::DEFAULT_TASK_URL . "6",
                ],
                'expectedWebResourceRetrievalHasSucceeded' => false,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 1,
                'expectedTaskOutput' => [
                    'messages' => [
                        [
                            'message' => 'Redirect limit reached',
                            'messageId' => 'http-retrieval-redirect-limit-reached',
                            'type' => 'error',
                        ],
                    ],
                ],
            ],
            'http redirect loop' => [
                'httpResponseFixtures' => [
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TestTaskFactory::DEFAULT_TASK_URL . "1",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TestTaskFactory::DEFAULT_TASK_URL . "2",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TestTaskFactory::DEFAULT_TASK_URL . "3",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TestTaskFactory::DEFAULT_TASK_URL,
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TestTaskFactory::DEFAULT_TASK_URL . "1",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TestTaskFactory::DEFAULT_TASK_URL . "2",
                ],
                'expectedWebResourceRetrievalHasSucceeded' => false,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 1,
                'expectedTaskOutput' => [
                    'messages' => [
                        [
                            'message' => 'Redirect loop detected',
                            'messageId' => 'http-retrieval-redirect-loop',
                            'type' => 'error',
                        ],
                    ],
                ],
            ],
            'http 404' => [
                'httpResponseFixtures' => [
                    'HTTP/1.1 404 Not Found',
                    'HTTP/1.1 404 Not Found',
                ],
                'expectedWebResourceRetrievalHasSucceeded' => false,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 1,
                'expectedTaskOutput' => [
                    'messages' => [
                        [
                            'message' => 'Not Found',
                            'messageId' => 'http-retrieval-404',
                            'type' => 'error',
                        ],
                    ],
                ]
            ],
            'http 500' => [
                'httpResponseFixtures' => [
                    'HTTP/1.1 500 Internal Server Error',
                    'HTTP/1.1 500 Internal Server Error',
                    'HTTP/1.1 500 Internal Server Error',
                    'HTTP/1.1 500 Internal Server Error',
                    'HTTP/1.1 500 Internal Server Error',
                    'HTTP/1.1 500 Internal Server Error',
                    'HTTP/1.1 500 Internal Server Error',
                    'HTTP/1.1 500 Internal Server Error',
                    'HTTP/1.1 500 Internal Server Error',
                    'HTTP/1.1 500 Internal Server Error',
                    'HTTP/1.1 500 Internal Server Error',
                    'HTTP/1.1 500 Internal Server Error',

                ],
                'expectedWebResourceRetrievalHasSucceeded' => false,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 1,
                'expectedTaskOutput' => [
                    'messages' => [
                        [
                            'message' => 'Internal Server Error',
                            'messageId' => 'http-retrieval-500',
                            'type' => 'error',
                        ],
                    ],
                ]
            ],
            'curl 3' => [
                'httpResponseFixtures' => [
                    ConnectExceptionFactory::create('CURL/3: foo'),
                ],
                'expectedWebResourceRetrievalHasSucceeded' => false,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 1,
                'expectedTaskOutput' => [
                    'messages' => [
                        [
                            'message' => 'Invalid resource URL',
                            'messageId' => 'http-retrieval-curl-code-3',
                            'type' => 'error',
                        ],
                    ],
                ]
            ],
            'curl 6' => [
                'httpResponseFixtures' => [
                    ConnectExceptionFactory::create('CURL/6: foo'),
                ],
                'expectedWebResourceRetrievalHasSucceeded' => false,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 1,
                'expectedTaskOutput' => [
                    'messages' => [
                        [
                            'message' => 'DNS lookup failure resolving resource domain name',
                            'messageId' => 'http-retrieval-curl-code-6',
                            'type' => 'error',
                        ],
                    ],
                ]
            ],
            'curl 28' => [
                'httpResponseFixtures' => [
                    ConnectExceptionFactory::create('CURL/28: foo'),
                ],
                'expectedWebResourceRetrievalHasSucceeded' => false,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 1,
                'expectedTaskOutput' => [
                    'messages' => [
                        [
                            'message' => 'Timeout reached retrieving resource',
                            'messageId' => 'http-retrieval-curl-code-28',
                            'type' => 'error',
                        ],
                    ],
                ]
            ],
            'curl unknown' => [
                'httpResponseFixtures' => [
                    ConnectExceptionFactory::create('CURL/55: foo'),
                ],
                'expectedWebResourceRetrievalHasSucceeded' => false,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 1,
                'expectedTaskOutput' => [
                    'messages' => [
                        [
                            'message' => '',
                            'messageId' => 'http-retrieval-curl-code-55',
                            'type' => 'error',
                        ],
                    ],
                ]
            ],
            'incorrect resource type' => [
                'httpResponseFixtures' => [
                    "HTTP/1.1 200 OK\nContent-type:application/pdf\n\nfoo",
                ],
                'expectedWebResourceRetrievalHasSucceeded' => true,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 0,
                'expectedTaskOutput' =>
                    null
            ],
            'empty content' => [
                'httpResponseFixtures' => [
                    "HTTP/1.1 200 OK\nContent-type:text/html",
                    "HTTP/1.1 200 OK\nContent-type:text/html",
                ],
                'expectedWebResourceRetrievalHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 0,
                'expectedTaskOutput' =>
                    null
            ],
        ];
    }

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
}
