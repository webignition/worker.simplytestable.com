<?php

namespace App\Tests\Functional\Services\TaskTypePerformer;

use App\Model\Task\TypeInterface;
use App\Tests\Services\TestTaskFactory;
use GuzzleHttp\Psr7\Response;
use App\Services\TaskTypePerformer\LinkCheckerConfigurationFactory;
use App\Services\TaskTypePerformer\LinkIntegrityTaskTypePerformer;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Factory\HtmlDocumentFactory;
use Psr\Http\Message\RequestInterface;

class LinkIntegrityTaskTypePerformerTest extends AbstractWebPageTaskTypePerformerTest
{
    /**
     * @var LinkIntegrityTaskTypePerformer
     */
    private $taskTypePerformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->taskTypePerformer = self::$container->get(LinkIntegrityTaskTypePerformer::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTaskTypePerformer()
    {
        return $this->taskTypePerformer;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTaskTypeString()
    {
        return TypeInterface::TYPE_LINK_INTEGRITY;
    }

    /**
     * @dataProvider performSuccessDataProvider
     *
     * @param array $httpFixtures
     * @param array $taskParameters
     * @param bool $expectedHasSucceeded
     * @param bool $expectedIsRetryable
     * @param int $expectedErrorCount
     * @param int $expectedWarningCount
     * @param array $expectedDecodedOutput
     */
    public function testPerformSuccess(
        $httpFixtures,
        $taskParameters,
        $expectedHasSucceeded,
        $expectedIsRetryable,
        $expectedErrorCount,
        $expectedWarningCount,
        $expectedDecodedOutput
    ) {
        $this->httpMockHandler->appendFixtures($httpFixtures);

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters),
        ]));

        $response = $this->taskTypePerformer->perform($task);

        $this->assertEquals($expectedHasSucceeded, $response->hasSucceeded());
        $this->assertEquals($expectedIsRetryable, $response->isRetryable());
        $this->assertEquals($expectedErrorCount, $response->getErrorCount());
        $this->assertEquals($expectedWarningCount, $response->getWarningCount());
        $this->assertEquals($expectedDecodedOutput, json_decode($response->getTaskOutput()->getOutput()));
    }

    /**
     * @return array
     */
    public function performSuccessDataProvider()
    {
        $notFoundResponse = new Response(404);
        $curl28ConnectException = ConnectExceptionFactory::create('CURL/28 Operation timed out.');

        return [
            'no links' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        '<!doctype html><html><head></head><body></body></html>'
                    ),
                ],
                'taskParameters' => [],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
            'single 200 OK link' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>'
                    ),
                    new Response(),
                ],
                'taskParameters' => [],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    (object)[
                        'context' => '<a href="/foo"></a>',
                        'state' => 200,
                        'type' => 'http',
                        'url' => 'http://example.com/foo',
                    ],
                ],
            ],
            'single 404 Not Found link' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>'
                    ),
                    $notFoundResponse,
                    $notFoundResponse,
                ],
                'taskParameters' => [],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    (object)[
                        'context' => '<a href="/foo"></a>',
                        'state' => 404,
                        'type' => 'http',
                        'url' => 'http://example.com/foo',
                    ],
                ],
            ],
            'single curl 28 link' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>'
                    ),
                    $curl28ConnectException,
                ],
                'taskParameters' => [],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    (object)[
                        'context' => '<a href="/foo"></a>',
                        'state' => 28,
                        'type' => 'curl',
                        'url' => 'http://example.com/foo',
                    ],
                ],
            ],
            'excluded urls' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>'
                    ),
                ],
                'taskParameters' => [
                    LinkCheckerConfigurationFactory::EXCLUDED_URLS_PARAMETER_NAME => [
                        'http://example.com/foo'
                    ],
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
            'excluded domains' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>'
                    ),
                ],
                'taskParameters' => [
                    LinkCheckerConfigurationFactory::EXCLUDED_DOMAINS_PARAMETER_NAME => [
                        'example.com'
                    ],
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
            'ignored schemes' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        HtmlDocumentFactory::load('ignored-link-integrity-schemes')
                    ),
                ],
                'taskParameters' => [],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
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
            new Response(
                200,
                ['content-type' => 'text/html'],
                '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>'
            ),
            new Response(200),
        ]);

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters)
        ]));

        $this->taskTypePerformer->perform($task);

        /* @var RequestInterface[]|array $historicalRequests */
        $historicalRequests = $this->httpHistoryContainer->getRequests();
        $this->assertCount(3, $historicalRequests);

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
            new Response(
                200,
                ['content-type' => 'text/html'],
                '<!doctype html><html><head></head><body><a href="/foo"></a></body></html>'
            ),
            new Response(200),
        ]);

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters)
        ]));

        $this->taskTypePerformer->perform($task);

        /* @var RequestInterface[]|array $historicalRequests */
        $historicalRequests = $this->httpHistoryContainer->getRequests();
        $this->assertCount(3, $historicalRequests);

        foreach ($historicalRequests as $historicalRequest) {
            $authorizationHeaderLine = $historicalRequest->getHeaderLine('authorization');

            $decodedAuthorizationHeaderValue = base64_decode(
                str_replace('Basic ', '', $authorizationHeaderLine)
            );

            $this->assertEquals($expectedRequestAuthorizationHeaderValue, $decodedAuthorizationHeaderValue);
        }
    }
}
