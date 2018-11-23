<?php

namespace App\Tests\Functional\Services\TaskTypePerformer;

use App\Model\Task\TypeInterface;
use App\Tests\Services\TestTaskFactory;
use GuzzleHttp\Psr7\Response;
use App\Services\TaskTypePerformer\CssValidationTaskTypePerformer;
use App\Tests\Factory\ConnectExceptionFactory;
use App\Tests\Factory\CssValidatorFixtureFactory;
use App\Tests\Factory\HtmlDocumentFactory;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;

class CssValidationTaskTypePerformerTest extends AbstractWebPageTaskTypePerformerTest
{
    /**
     * @var CssValidationTaskTypePerformer
     */
    private $taskTypePerformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->taskTypePerformer = self::$container->get(CssValidationTaskTypePerformer::class);
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
        return TypeInterface::TYPE_CSS_VALIDATION;
    }

    /**
     * @dataProvider performSuccessDataProvider
     *
     * @param array $httpFixtures
     * @param array $taskParameters
     * @param string $cssValidatorOutput
     * @param bool $expectedHasSucceeded
     * @param bool $expectedIsRetryable
     * @param int $expectedErrorCount
     * @param int $expectedWarningCount
     * @param array $expectedDecodedOutput
     */
    public function testPerformSuccess(
        $httpFixtures,
        $taskParameters,
        $cssValidatorOutput,
        $expectedHasSucceeded,
        $expectedIsRetryable,
        $expectedErrorCount,
        $expectedWarningCount,
        $expectedDecodedOutput
    ) {
        $this->httpMockHandler->appendFixtures($httpFixtures);

        $task = $this->testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults([
                'type' => $this->getTaskTypeString(),
                'parameters' => json_encode($taskParameters),
            ])
        );

        CssValidatorFixtureFactory::set($cssValidatorOutput);

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
        $internalServerErrorResponse = new Response(500);
        $curl6ConnectException = ConnectExceptionFactory::create('CURL/6 foo');

        return [
            'unknown validator exception' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(200, ['content-type' => 'text/html'], 'foo'),
                ],
                'taskParameters' => [],
                'cssValidatorOutput' => CssValidatorFixtureFactory::load('unknown-exception'),
                'expectedHasSucceeded' => false,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    (object)[
                        'message' => 'Unknown error',
                        'class' => 'css-validation-exception-unknown',
                        'type' => 'error',
                        'context' => '',
                        'ref' => 'http://example.com/',
                        'line_number' => 0,
                    ],
                ],
            ],
            'no errors, ignore warnings' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(200, ['content-type' => 'text/html'], 'foo'),
                ],
                'taskParameters' => [
                    'ignore-warnings' => true,
                ],
                'cssValidatorOutput' => CssValidatorFixtureFactory::load('1-vendor-extension-warning'),
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
            'no errors, ignore vendor extension warnings' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(200, ['content-type' => 'text/html'], 'foo'),
                ],
                'taskParameters' => [
                    'vendor-extensions' => VendorExtensionSeverityLevel::LEVEL_IGNORE,
                ],
                'cssValidatorOutput' => CssValidatorFixtureFactory::load('1-vendor-extension-warning'),
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
            'three errors' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(200, ['content-type' => 'text/html'], 'foo'),
                ],
                'taskParameters' => [
                    'ignore-warnings' => true,
                ],
                'cssValidatorOutput' => CssValidatorFixtureFactory::load('3-errors'),
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 3,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    (object)[
                        'message' => 'one',
                        'context' => 'audio, canvas, video',
                        'line_number' => 1,
                        'type' => 'error',
                        'ref' => 'http://example.com/',
                    ],
                    (object)[
                        'message' => 'two',
                        'context' => 'html',
                        'line_number' => 2,
                        'type' => 'error',
                        'ref' => 'http://example.com/',
                    ],
                    (object)[
                        'message' => 'three',
                        'context' => '.hide-text',
                        'line_number' => 3,
                        'type' => 'error',
                        'ref' => 'http://example.com/',
                    ],
                ],
            ],
            'http 404 getting linked resource' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        HtmlDocumentFactory::load('empty-body-single-css-link')
                    ),
                    $notFoundResponse,
                    $notFoundResponse,
                ],
                'taskParameters' => [],
                'cssValidatorOutput' => CssValidatorFixtureFactory::load('no-messages'),
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    (object)[
                        'message' => 'http-retrieval-404',
                        'type' => 'error',
                        'context' => '',
                        'ref' => 'http://example.com/style.css',
                        'line_number' => 0,
                    ],
                ],
            ],
            'http 500 getting linked resource' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        HtmlDocumentFactory::load('empty-body-single-css-link')
                    ),
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                    $internalServerErrorResponse,
                ],
                'taskParameters' => [],
                'cssValidatorOutput' => CssValidatorFixtureFactory::load('no-messages'),
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    (object)[
                        'message' => 'http-retrieval-500',
                        'type' => 'error',
                        'context' => '',
                        'ref' => 'http://example.com/style.css',
                        'line_number' => 0,
                    ],
                ],
            ],
            'curl 6 getting linked resource' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        HtmlDocumentFactory::load('empty-body-single-css-link')
                    ),
                    $curl6ConnectException,
                    $curl6ConnectException,
                    $curl6ConnectException,
                    $curl6ConnectException,
                    $curl6ConnectException,
                    $curl6ConnectException,
                    $curl6ConnectException,
                    $curl6ConnectException,
                    $curl6ConnectException,
                    $curl6ConnectException,
                    $curl6ConnectException,
                    $curl6ConnectException,
                ],
                'taskParameters' => [],
                'cssValidatorOutput' => CssValidatorFixtureFactory::load('no-messages'),
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    (object)[
                        'message' => 'http-retrieval-curl-code-6',
                        'type' => 'error',
                        'context' => '',
                        'ref' => 'http://example.com/style.css',
                        'line_number' => 0,
                    ],
                ],
            ],
            'invalid content type getting linked resource' => [
                'httpFixtures' => [
                    new Response(200, ['content-type' => 'text/html']),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        HtmlDocumentFactory::load('empty-body-single-css-link')
                    ),
                    new Response(200, ['content-type' => 'application/pdf']),
                ],
                'taskParameters' => [],
                'cssValidatorOutput' => CssValidatorFixtureFactory::load('no-messages'),
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    (object)[
                        'message' => 'invalid-content-type:application/pdf',
                        'type' => 'error',
                        'context' => '',
                        'ref' => 'http://example.com/style.css',
                        'line_number' => 0,
                    ],
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
            new Response(
                200,
                ['content-type' => 'text/html'],
                HtmlDocumentFactory::load('empty-body-single-css-link')
            ),
            new Response(200, ['content-type' => 'text/css']),
            new Response(200, ['content-type' => 'text/css']),
        ]);

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters)
        ]));

        CssValidatorFixtureFactory::set(CssValidatorFixtureFactory::load('no-messages'));

        $this->taskTypePerformer->perform($task);

        /* @var array $historicalRequests */
        $historicalRequests = $this->httpHistoryContainer->getRequests();
        $this->assertCount(4, $historicalRequests);

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
                HtmlDocumentFactory::load('empty-body-single-css-link')
            ),
            new Response(200, ['content-type' => 'text/css']),
            new Response(200, ['content-type' => 'text/css']),
        ]);

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters)
        ]));

        CssValidatorFixtureFactory::set(CssValidatorFixtureFactory::load('no-messages'));

        $this->taskTypePerformer->perform($task);

        /* @var array $historicalRequests */
        $historicalRequests = $this->httpHistoryContainer->getRequests();
        $this->assertCount(4, $historicalRequests);

        foreach ($historicalRequests as $historicalRequest) {
            $authorizationHeaderLine = $historicalRequest->getHeaderLine('authorization');

            $decodedAuthorizationHeaderValue = base64_decode(
                str_replace('Basic ', '', $authorizationHeaderLine)
            );

            $this->assertEquals($expectedRequestAuthorizationHeaderValue, $decodedAuthorizationHeaderValue);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
