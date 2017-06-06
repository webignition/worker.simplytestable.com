<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Message\RequestInterface;
use Mockery\MockInterface;
use SimplyTestable\WorkerBundle\Services\TaskDriver\TaskDriver;
use SimplyTestable\WorkerBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;

abstract class FooWebResourceTaskDriverTest extends BaseSimplyTestableTestCase
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->removeAllTasks();
        $this->clearMemcacheHttpCache();
    }

    /**
     * @return TaskDriver
     */
    abstract protected function getTaskDriver();

    /**
     * @return string
     */
    abstract protected function getTaskTypeString();

    public function testPerformNonCurlConnectException()
    {
        /* @var $request MockInterface|RequestInterface */
        $request = \Mockery::mock(RequestInterface::class);

        $this->setHttpFixtures($this->buildHttpFixtureSet([
            new ConnectException('foo', $request)
        ]));

        $task = $this->getTaskFactory()->create(
            TaskFactory::createTaskValuesFromDefaults()
        );

        $this->setExpectedException(
            ConnectException::class,
            'foo',
            0
        );

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
        $this->setHttpFixtures($this->buildHttpFixtureSet($httpResponseFixtures));

        $task = $this->getTaskFactory()->create(
            TaskFactory::createTaskValuesFromDefaults()
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
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TaskFactory::DEFAULT_TASK_URL . "1",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TaskFactory::DEFAULT_TASK_URL . "2",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TaskFactory::DEFAULT_TASK_URL . "3",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TaskFactory::DEFAULT_TASK_URL . "4",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TaskFactory::DEFAULT_TASK_URL . "5",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TaskFactory::DEFAULT_TASK_URL . "6",
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
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TaskFactory::DEFAULT_TASK_URL . "1",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TaskFactory::DEFAULT_TASK_URL . "2",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TaskFactory::DEFAULT_TASK_URL . "3",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TaskFactory::DEFAULT_TASK_URL,
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TaskFactory::DEFAULT_TASK_URL . "1",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TaskFactory::DEFAULT_TASK_URL . "2",
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
            'curl 3' => [
                'httpResponseFixtures' => [
                    'CURL/3: foo',
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
                    'CURL/6: foo',
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
                    'CURL/28: foo',
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
                    'CURL/55: foo',
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
                ],
                'expectedWebResourceRetrievalHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 0,
                'expectedTaskOutput' =>
                    null
            ],
        ];
    }
}
