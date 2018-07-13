<?php

namespace Tests\AppBundle\Functional\Services\TaskDriver;

use GuzzleHttp\Psr7\Response;
use AppBundle\Services\TaskDriver\JsLintTaskDriver;
use AppBundle\Services\TaskTypeService;
use Tests\AppBundle\Factory\ConnectExceptionFactory;
use Tests\AppBundle\Factory\HtmlDocumentFactory;
use Tests\AppBundle\Factory\JsLintFixtureFactory;
use Tests\AppBundle\Factory\TestTaskFactory;
use webignition\NodeJslintOutput\Exception as NodeJslintOutputException;

class JsLintTaskDriverTest extends AbstractWebPageTaskDriverTest
{
    /**
     * @var JsLintTaskDriver
     */
    private $taskDriver;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->taskDriver = self::$container->get(JsLintTaskDriver::class);
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
        return strtolower(TaskTypeService::JS_STATIC_ANALYSIS_NAME);
    }

    public function testIncorrectPathToNodeJsLint()
    {
        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], HtmlDocumentFactory::load('empty-body-single-js-link')),
            new Response(200, ['content-type' => 'application/javascript']),
            new Response(200, ['content-type' => 'application/javascript']),
        ]);

        $task = $this->testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults([
                'type' => $this->getTaskTypeString(),
            ])
        );

        JsLintFixtureFactory::set([
            JsLintFixtureFactory::load('incorrect-path-to-node-jslint.txt')
        ]);

        $this->expectException(NodeJslintOutputException::class);
        $this->expectExceptionMessage('node-jslint not found at "/home/example/node_modules/jslint/bin/jslint.js"');
        $this->expectExceptionCode(3);

        $this->taskDriver->perform($task);
    }

    /**
     * @dataProvider performSuccessDataProvider
     *
     * @param array $httpFixtures
     * @param array $taskParameters
     * @param string[] $jsLintRawOutput
     * @param bool $expectedHasSucceeded
     * @param bool $expectedIsRetryable
     * @param int $expectedErrorCount
     * @param int $expectedWarningCount
     * @param array $expectedDecodedOutput
     */
    public function testPerformSuccess(
        array $httpFixtures,
        array $taskParameters,
        array $jsLintRawOutput,
        $expectedHasSucceeded,
        $expectedIsRetryable,
        $expectedErrorCount,
        $expectedWarningCount,
        array $expectedDecodedOutput
    ) {
        $this->httpMockHandler->appendFixtures($httpFixtures);

        $task = $this->testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults([
                'type' => $this->getTaskTypeString(),
                'parameters' => json_encode($taskParameters),
            ])
        );

        JsLintFixtureFactory::set($jsLintRawOutput);

        $taskDriverResponse = $this->taskDriver->perform($task);
        $decodedTaskOutput = json_decode($taskDriverResponse->getTaskOutput()->getOutput(), true);

        $this->assertEquals($expectedHasSucceeded, $taskDriverResponse->hasSucceeded());
        $this->assertEquals($expectedIsRetryable, $taskDriverResponse->isRetryable());
        $this->assertEquals($expectedErrorCount, $taskDriverResponse->getErrorCount());
        $this->assertEquals($expectedWarningCount, $taskDriverResponse->getWarningCount());
        $this->assertEquals($expectedDecodedOutput, $decodedTaskOutput);
    }

    /**
     * @return array
     */
    public function performSuccessDataProvider()
    {
        $textHtmlNoBodyResponse = new Response(200, ['content-type' => 'text/html']);
        $applicationJavascriptNoBodyResponse = new Response(200, ['content-type' => 'application/javascript']);
        $notFoundResponse = new Response(404);
        $internalServerErrorResponse = new Response(500);
        $curl6ConnectException = ConnectExceptionFactory::create('CURL/6 foo');
        $curl28Exception = ConnectExceptionFactory::create('CURL/28 foo');
        $redirectResponse = new Response(
            301,
            ['content-type' => 'application/javascript', 'location' => 'http://example.com/redirected.js']
        );

        return [
            'no js' => [
                'httpFixtures' => [
                    $textHtmlNoBodyResponse,
                    new Response(200, ['content-type' => 'text/html'], HtmlDocumentFactory::load('minimal')),
                ],
                'taskParameters' => [],
                'jsLintRawOutput' => [JsLintFixtureFactory::load('no-errors.json')],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
            'script elements, has errors' => [
                'httpFixtures' => [
                    $textHtmlNoBodyResponse,
                    new Response(200, ['content-type' => 'text/html'], HtmlDocumentFactory::load('js-script-elements')),
                    $applicationJavascriptNoBodyResponse,
                    $applicationJavascriptNoBodyResponse,
                ],
                'taskParameters' => [
                    'domains-to-ignore' => [
                        'bar.example.com'
                    ],
                    'jslint-option-sloppy' => true,
                    'jslint-option-debug' => false,
                    'jslint-option-maxerr' => 50,
                    'jslint-option-predef' => 'window',
                ],
                'jsLintRawOutput' => [
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('too-many-errors-stopped-at-seven-percent.json'),
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    'http://example.com/foo.js' => [
                        'statusLine' => [],
                        'entries' => [],
                    ],
                    '7dc508faa82075c1039d38c6522c2124' => [
                        'statusLine' => '/tmp/2be27b536970c6988a1f387359237529:1:1379085668.7688.js',
                        'entries' => [
                            [
                                'headerLine' => [
                                    'errorNumber' => 1,
                                    'errorMessage' => "Missing 'use strict' statement.",
                                ],
                                'fragmentLine' => [
                                    'fragment' => 'var excessivelyLongName = \'bar\';',
                                    'lineNumber' => 6,
                                    'columnNumber' => 5,
                                ],
                            ],
                            [
                                'headerLine' => [
                                    'errorNumber' => 1,
                                    'errorMessage' => 'Too many errors. (7% scanned).',
                                ],
                                'fragmentLine' => [
                                    'fragment' => null,
                                    'lineNumber' => 113,
                                    'columnNumber' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'redirect impacts script url' => [
                'httpFixtures' => [
                    new Response(301, ['content-type' => 'text/html', 'location' => 'http://sub.example.com']),
                    $textHtmlNoBodyResponse,
                    new Response(301, ['content-type' => 'text/html', 'location' => 'http://sub.example.com']),
                    new Response(200, ['content-type' => 'text/html'], HtmlDocumentFactory::load('js-script-elements')),
                    $applicationJavascriptNoBodyResponse,
                    $applicationJavascriptNoBodyResponse,
                ],
                'taskParameters' => [
                    'domains-to-ignore' => [
                        'bar.example.com'
                    ],
                    'jslint-option-sloppy' => true,
                    'jslint-option-debug' => false,
                    'jslint-option-maxerr' => 50,
                    'jslint-option-predef' => 'window',
                ],
                'jsLintRawOutput' => [
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('too-many-errors-stopped-at-seven-percent.json'),
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    'http://sub.example.com/foo.js' => [
                        'statusLine' => [],
                        'entries' => [],
                    ],
                    '7dc508faa82075c1039d38c6522c2124' => [
                        'statusLine' => '/tmp/2be27b536970c6988a1f387359237529:1:1379085668.7688.js',
                        'entries' => [
                            [
                                'headerLine' => [
                                    'errorNumber' => 1,
                                    'errorMessage' => "Missing 'use strict' statement.",
                                ],
                                'fragmentLine' => [
                                    'fragment' => 'var excessivelyLongName = \'bar\';',
                                    'lineNumber' => 6,
                                    'columnNumber' => 5,
                                ],
                            ],
                            [
                                'headerLine' => [
                                    'errorNumber' => 1,
                                    'errorMessage' => 'Too many errors. (7% scanned).',
                                ],
                                'fragmentLine' => [
                                    'fragment' => null,
                                    'lineNumber' => 113,
                                    'columnNumber' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'script elements, stopped' => [
                'httpFixtures' => [
                    $textHtmlNoBodyResponse,
                    new Response(200, ['content-type' => 'text/html'], HtmlDocumentFactory::load('js-script-elements')),
                    $applicationJavascriptNoBodyResponse,
                    $applicationJavascriptNoBodyResponse,
                ],
                'taskParameters' => [
                    'domains-to-ignore' => [
                        'bar.example.com'
                    ],
                    'jslint-option-predef' => ['window'],
                ],
                'jsLintRawOutput' => [
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('stopped-no-error.json'),
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    'http://example.com/foo.js' => [
                        'statusLine' => [],
                        'entries' => [],
                    ],
                    '7dc508faa82075c1039d38c6522c2124' => [
                        'statusLine' => '/tmp/41ce43a0e2a1c6530cda88fe7ee06d48:192:1381751332.6812.js',
                        'entries' => [
                            [
                                'headerLine' => [
                                    'errorNumber' => 1,
                                    'errorMessage' => 'Stopping. (75% scanned).',
                                ],
                                'fragmentLine' => [
                                    'fragment' => null,
                                    'lineNumber' => 3,
                                    'columnNumber' => 8,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'invalid content type exception on linked resource' => [
                'httpFixtures' => [
                    $textHtmlNoBodyResponse,
                    new Response(200, ['content-type' => 'text/html'], HtmlDocumentFactory::load('js-script-elements')),
                    $textHtmlNoBodyResponse,
                    $textHtmlNoBodyResponse,
                ],
                'taskParameters' => [
                    'domains-to-ignore' => 'foo',
                ],
                'jsLintRawOutput' => [
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('no-errors.json'),
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 2,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    'http://example.com/foo.js' => [
                        'statusLine' => 'failed',
                        'errorReport' => [
                            'reason' => 'InvalidContentTypeException',
                            'contentType' => 'text/html',
                        ],
                    ],
                    '7dc508faa82075c1039d38c6522c2124' => [
                        'statusLine' => '/tmp/2be27b536970c6988a1f387359237529:1:1379085668.7688.js',
                        'entries' => [],
                    ],
                    'http://bar.example.com/bar.js' => [
                        'statusLine' => 'failed',
                        'errorReport' => [
                            'reason' => 'InvalidContentTypeException',
                            'contentType' => 'text/html',
                        ],
                    ],
                ],
            ],
            'http 404 getting linked resource' => [
                'httpFixtures' => [
                    $textHtmlNoBodyResponse,
                    new Response(200, ['content-type' => 'text/html'], HtmlDocumentFactory::load('js-script-elements')),
                    $notFoundResponse,
                    $notFoundResponse,
                ],
                'taskParameters' => [
                    'domains-to-ignore' => [
                        'bar.example.com'
                    ],
                ],
                'jsLintRawOutput' => [
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('no-errors.json'),
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    'http://example.com/foo.js' => [
                        'statusLine' => 'failed',
                        'errorReport' => [
                            'reason' => 'webResourceException',
                            'statusCode' => 404,
                        ],
                    ],
                    '7dc508faa82075c1039d38c6522c2124' => [
                        'statusLine' => '/tmp/2be27b536970c6988a1f387359237529:1:1379085668.7688.js',
                        'entries' => [],
                    ],
                ],
            ],
            'http 500 getting linked resource' => [
                'httpFixtures' => [
                    $textHtmlNoBodyResponse,
                    new Response(200, ['content-type' => 'text/html'], HtmlDocumentFactory::load('js-script-elements')),
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
                'taskParameters' => [
                    'domains-to-ignore' => [
                        'bar.example.com'
                    ],
                ],
                'jsLintRawOutput' => [
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('no-errors.json'),
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    'http://example.com/foo.js' => [
                        'statusLine' => 'failed',
                        'errorReport' => [
                            'reason' => 'webResourceException',
                            'statusCode' => 500,
                        ],
                    ],
                    '7dc508faa82075c1039d38c6522c2124' => [
                        'statusLine' => '/tmp/2be27b536970c6988a1f387359237529:1:1379085668.7688.js',
                        'entries' => [],
                    ],
                ],
            ],
            'curl 6 getting linked resource' => [
                'httpFixtures' => [
                    $textHtmlNoBodyResponse,
                    new Response(200, ['content-type' => 'text/html'], HtmlDocumentFactory::load('js-script-elements')),
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
                'taskParameters' => [
                    'domains-to-ignore' => [
                        'bar.example.com'
                    ],
                ],
                'jsLintRawOutput' => [
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('no-errors.json'),
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    'http://example.com/foo.js' => [
                        'statusLine' => 'failed',
                        'errorReport' => [
                            'reason' => 'curlException',
                            'statusCode' => 6
                        ],
                    ],
                    '7dc508faa82075c1039d38c6522c2124' => [
                        'statusLine' => '/tmp/2be27b536970c6988a1f387359237529:1:1379085668.7688.js',
                        'entries' => [],
                    ],
                ],
            ],
            'curl 28 getting linked resource' => [
                'httpFixtures' => [
                    $textHtmlNoBodyResponse,
                    new Response(200, ['content-type' => 'text/html'], HtmlDocumentFactory::load('js-script-elements')),
                    $curl28Exception,
                    $curl28Exception,
                    $curl28Exception,
                    $curl28Exception,
                    $curl28Exception,
                    $curl28Exception,
                    $curl28Exception,
                    $curl28Exception,
                    $curl28Exception,
                    $curl28Exception,
                    $curl28Exception,
                    $curl28Exception,
                ],
                'taskParameters' => [
                    'domains-to-ignore' => [
                        'bar.example.com'
                    ],
                ],
                'jsLintRawOutput' => [
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('no-errors.json'),
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    'http://example.com/foo.js' => [
                        'statusLine' => 'failed',
                        'errorReport' => [
                            'reason' => 'curlException',
                            'statusCode' => 28
                        ],
                    ],
                    '7dc508faa82075c1039d38c6522c2124' => [
                        'statusLine' => '/tmp/2be27b536970c6988a1f387359237529:1:1379085668.7688.js',
                        'entries' => [],
                    ],
                ],
            ],
            'too many redirects getting linked resource' => [
                'httpFixtures' => [
                    $textHtmlNoBodyResponse,
                    new Response(200, ['content-type' => 'text/html'], HtmlDocumentFactory::load('js-script-elements')),
                    $redirectResponse,
                    $redirectResponse,
                    $redirectResponse,
                    $redirectResponse,
                    $redirectResponse,
                    $redirectResponse,
                    $redirectResponse,
                    $redirectResponse,
                    $redirectResponse,
                    $redirectResponse,
                    $redirectResponse,
                    $redirectResponse,
                ],
                'taskParameters' => [
                    'domains-to-ignore' => [
                        'bar.example.com'
                    ],
                ],
                'jsLintRawOutput' => [
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('no-errors.json'),
                    JsLintFixtureFactory::load('no-errors.json'),
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    'http://example.com/foo.js' => [
                        'statusLine' => 'failed',
                        'errorReport' => [
                            'reason' => 'webResourceException',
                            'statusCode' => 301,
                        ],
                    ],
                    '7dc508faa82075c1039d38c6522c2124' => [
                        'statusLine' => '/tmp/2be27b536970c6988a1f387359237529:1:1379085668.7688.js',
                        'entries' => [],
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
            new Response(200, ['content-type' => 'text/html'], HtmlDocumentFactory::load('empty-body-single-js-link')),
            new Response(200, ['content-type' => 'application/javascript']),
            new Response(200, ['content-type' => 'application/javascript']),
        ]);

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters)
        ]));

        JsLintFixtureFactory::set([
            JsLintFixtureFactory::load('no-errors.json')
        ]);

        $this->taskDriver->perform($task);

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
            new Response(200, ['content-type' => 'text/html'], HtmlDocumentFactory::load('empty-body-single-js-link')),
            new Response(200, ['content-type' => 'application/javascript']),
            new Response(200, ['content-type' => 'application/javascript']),
        ]);

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters)
        ]));

        JsLintFixtureFactory::set([
            JsLintFixtureFactory::load('no-errors.json')
        ]);

        $this->taskDriver->perform($task);

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
