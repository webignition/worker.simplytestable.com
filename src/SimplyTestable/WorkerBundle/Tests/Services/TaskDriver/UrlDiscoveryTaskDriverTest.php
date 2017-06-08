<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Services\TaskDriver\UrlDiscoveryTaskDriver;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use SimplyTestable\WorkerBundle\Tests\Factory\HtmlDocumentFactory;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;

class UrlDiscoveryTaskDriverTest extends WebResourceTaskDriverTest
{
    /**
     * @var UrlDiscoveryTaskDriver
     */
    private $taskDriver;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->taskDriver = $this->container->get('simplytestable.services.taskdriver.urldiscovery');
    }

    /**
     * @inheritdoc
     */
    protected function getTaskDriver()
    {
        return $this->taskDriver;
    }

    /**
     * @inheritdoc
     */
    protected function getTaskTypeString()
    {
        return strtolower(TaskTypeService::URL_DISCOVERY_NAME);
    }

    /**
     * @dataProvider performDataProvider
     *
     * @param $httpFixture
     * @param $taskParameters
     * @param $expectedHasSucceeded
     * @param $expectedIsRetryable
     * @param $expectedDecodedOutput
     */
    public function testPerform(
        $httpFixture,
        $taskParameters,
        $expectedHasSucceeded,
        $expectedIsRetryable,
        $expectedDecodedOutput
    ) {
        $this->setHttpFixtures([$httpFixture]);

        $task = $this->getTaskFactory()->create(
            TaskFactory::createTaskValuesFromDefaults([
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
                'httpFixture' => sprintf(
                    "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                    HtmlDocumentFactory::load('minimal')
                ),
                'taskParameters' => [],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedDecodedOutput' => [],
            ],
            'no scope' => [
                'httpFixture' => sprintf(
                    "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                    HtmlDocumentFactory::load('css-link-js-link-image-anchors')
                ),
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
                'httpFixture' => sprintf(
                    "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                    HtmlDocumentFactory::load('css-link-js-link-image-anchors')
                ),
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
     * @inheritdoc
     */
    public function testSetCookiesOnHttpClient($taskParameters, $expectedRequestCookieHeader)
    {
        $this->setHttpFixtures([
            "HTTP/1.0 200\nContent-Type:text/html\n\n<!doctype html><html>"
        ]);

        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters)
        ]));

        $this->taskDriver->perform($task);

        $request = $this->getHttpClientService()->getHistory()->getLastRequest();
        $this->assertEquals($expectedRequestCookieHeader, $request->getHeader('cookie'));
    }

    /**
     * @dataProvider httpAuthDataProvider
     * @inheritdoc
     */
    public function testSetHttpAuthOnHttpClient($taskParameters, $expectedRequestAuthorizationHeaderValue)
    {
        $this->setHttpFixtures([
            "HTTP/1.0 200\nContent-Type:text/html\n\n<!doctype html><html>"
        ]);

        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters),
        ]));

        $this->taskDriver->perform($task);

        $request = $this->getHttpClientService()->getHistory()->getLastRequest();

        $decodedAuthorizationHeaderValue = base64_decode(
            str_replace('Basic', '', $request->getHeader('authorization'))
        );

        $this->assertEquals($expectedRequestAuthorizationHeaderValue, $decodedAuthorizationHeaderValue);
    }
}
