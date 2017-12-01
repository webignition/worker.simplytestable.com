<?php

namespace Tests\WorkerBundle\Functional\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\TaskDriver\UrlDiscoveryTaskDriver;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use Tests\WorkerBundle\Factory\HtmlDocumentFactory;
use Tests\WorkerBundle\Factory\TestTaskFactory;

class UrlDiscoveryTaskDriverTest extends WebResourceTaskDriverTest
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
        $this->taskDriver = $this->container->get(UrlDiscoveryTaskDriver::class);
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
        return strtolower(TaskTypeService::URL_DISCOVERY_NAME);
    }

    /**
     * @dataProvider performDataProvider
     *
     * @param $httpFixtures
     * @param $taskParameters
     * @param $expectedHasSucceeded
     * @param $expectedIsRetryable
     * @param $expectedDecodedOutput
     */
    public function testPerform(
        $httpFixtures,
        $taskParameters,
        $expectedHasSucceeded,
        $expectedIsRetryable,
        $expectedDecodedOutput
    ) {
        $this->setHttpFixtures($httpFixtures);

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
    public function performDataProvider()
    {
        return [
            'no urls' => [
                'httpFixtures' => [
                    "HTTP/1.1 200 OK\nContent-type:text/html",
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                        HtmlDocumentFactory::load('minimal')
                    ),
                ],
                'taskParameters' => [],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedDecodedOutput' => [],
            ],
            'no scope' => [
                'httpFixtures' => [
                    "HTTP/1.1 200 OK\nContent-type:text/html",
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
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
                    "HTTP/1.1 200 OK\nContent-type:text/html",
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
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
    public function testSetCookiesOnHttpClient($taskParameters, $expectedRequestCookieHeader)
    {
        $this->setHttpFixtures([
            "HTTP/1.0 200\nContent-Type:text/html",
            "HTTP/1.0 200\nContent-Type:text/html\n\n<!doctype html><html>",
        ]);

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters)
        ]));

        $this->taskDriver->perform($task);

        $request = $this->container->get(HttpClientService::class)->getHistory()->getLastRequest();
        $this->assertEquals($expectedRequestCookieHeader, $request->getHeader('cookie'));
    }

    /**
     * @dataProvider httpAuthDataProvider
     *
     * {@inheritdoc}
     */
    public function testSetHttpAuthOnHttpClient($taskParameters, $expectedRequestAuthorizationHeaderValue)
    {
        $this->setHttpFixtures([
            "HTTP/1.0 200\nContent-Type:text/html",
            "HTTP/1.0 200\nContent-Type:text/html\n\n<!doctype html><html>"
        ]);

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters),
        ]));

        $this->taskDriver->perform($task);

        $request = $this->container->get(HttpClientService::class)->getHistory()->getLastRequest();

        $decodedAuthorizationHeaderValue = base64_decode(
            str_replace('Basic', '', $request->getHeader('authorization'))
        );

        $this->assertEquals($expectedRequestAuthorizationHeaderValue, $decodedAuthorizationHeaderValue);
    }
}
