<?php

namespace App\Tests\Functional\Services\TaskDriver;

use App\Model\Task\TypeInterface;
use GuzzleHttp\Psr7\Response;
use App\Services\TaskDriver\UrlDiscoveryTaskDriver;
use App\Tests\Factory\HtmlDocumentFactory;
use App\Tests\Factory\TestTaskFactory;

class UrlDiscoveryTaskDriverTest extends AbstractWebPageTaskDriverTest
{
    /**
     * @var UrlDiscoveryTaskDriver
     */
    private $taskDriver;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->taskDriver = self::$container->get(UrlDiscoveryTaskDriver::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTaskDriver()
    {
        return $this->taskDriver;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTaskTypeString()
    {
        return TypeInterface::TYPE_URL_DISCOVERY;
    }

    /**
     * @dataProvider performSuccessDataProvider
     *
     * @param $httpFixtures
     * @param $taskParameters
     * @param $expectedHasSucceeded
     * @param $expectedIsRetryable
     * @param $expectedDecodedOutput
     */
    public function testPerformSuccess(
        $httpFixtures,
        $taskParameters,
        $expectedHasSucceeded,
        $expectedIsRetryable,
        $expectedDecodedOutput
    ) {
        $this->httpMockHandler->appendFixtures($httpFixtures);

        $task = $this->testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults([
                'type' => $this->getTaskTypeString(),
                'parameters' => json_encode($taskParameters),
            ])
        );

        $taskDriverResponse = $this->taskDriver->perform($task);

        $this->assertEquals($expectedHasSucceeded, $taskDriverResponse->hasSucceeded());
        $this->assertEquals($expectedIsRetryable, $taskDriverResponse->isRetryable());
        $this->assertEquals($expectedDecodedOutput, json_decode($taskDriverResponse->getTaskOutput()->getOutput()));
    }

    /**
     * @return array
     */
    public function performSuccessDataProvider()
    {
        return [
            'no urls' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(200, ['content-type' => 'text/html'], HtmlDocumentFactory::load('minimal')),
                ],
                'taskParameters' => [],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedDecodedOutput' => [],
            ],
            'no scope' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        HtmlDocumentFactory::load('css-link-js-link-image-anchors')
                    ),
                ],
                'taskParameters' => [],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedDecodedOutput' => [
                    'http://example.com/foo/anchor1',
                    'http://www.example.com/foo/anchor2',
                    'http://bar.example.com/bar/anchor',
                    'https://www.example.com/foo/anchor1',
                ],
            ],
            'has scope' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        HtmlDocumentFactory::load('css-link-js-link-image-anchors')
                    ),
                ],
                'taskParameters' => [
                    'scope' => [
                        'http://example.com',
                        'http://www.example.com',
                    ]
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedDecodedOutput' => [
                    'http://example.com/foo/anchor1',
                    'http://www.example.com/foo/anchor2',
                    'https://www.example.com/foo/anchor1',
                ],
            ],
        ];
    }

    /**
     * @dataProvider cookiesDataProvider
     *
     * {@inheritdoc}
     */
    public function testSetCookiesOnRequests($taskParameters, $expectedRequestCookieHeader)
    {
        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], '<!doctype html><html>'),
        ]);

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters)
        ]));

        $this->taskDriver->perform($task);

        /* @var array $historicalRequests */
        $historicalRequests = $this->httpHistoryContainer->getRequests();
        $this->assertCount(2, $historicalRequests);

        foreach ($historicalRequests as $historicalRequest) {
            $cookieHeaderLine = $historicalRequest->getHeaderLine('cookie');
            $this->assertEquals($expectedRequestCookieHeader, $cookieHeaderLine);
        }
    }

    /**
     * @dataProvider httpAuthDataProvider
     *
     * {@inheritdoc}
     */
    public function testSetHttpAuthenticationOnRequests($taskParameters, $expectedRequestAuthorizationHeaderValue)
    {
        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], '<!doctype html><html>'),
        ]);

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters),
        ]));

        $this->taskDriver->perform($task);

        /* @var array $historicalRequests */
        $historicalRequests = $this->httpHistoryContainer->getRequests();
        $this->assertCount(2, $historicalRequests);

        foreach ($historicalRequests as $historicalRequest) {
            $authorizationHeaderLine = $historicalRequest->getHeaderLine('authorization');

            $decodedAuthorizationHeaderValue = base64_decode(
                str_replace('Basic ', '', $authorizationHeaderLine)
            );

            $this->assertEquals($expectedRequestAuthorizationHeaderValue, $decodedAuthorizationHeaderValue);
        }
    }
}
