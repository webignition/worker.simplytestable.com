<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver;

use phpmock\mockery\PHPMockery;
use SimplyTestable\WorkerBundle\Services\TaskDriver\JsLintTaskDriver;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use SimplyTestable\WorkerBundle\Tests\Factory\ConnectExceptionFactory;
use SimplyTestable\WorkerBundle\Tests\Factory\HtmlDocumentFactory;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;
use webignition\NodeJslintOutput\Exception as NodeJslintOutputException;

class JsLintTaskDriverTest extends WebResourceTaskDriverTest
{
    /**
     * @var JsLintTaskDriver
     */
    private $taskDriver;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->taskDriver = $this->container->get('simplytestable.services.taskdriver.jslint');
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
        return strtolower(TaskTypeService::JS_STATIC_ANALYSIS_NAME);
    }

    public function testIncorrectPathToNodeJsLint()
    {
        $this->setHttpFixtures([
            sprintf(
                "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                HtmlDocumentFactory::load('empty-body-single-js-link')
            ),
            "HTTP/1.1 200 OK\nContent-type:application/javascript\n\n",
        ]);

        $task = $this->getTaskFactory()->create(
            TaskFactory::createTaskValuesFromDefaults([
                'type' => $this->getTaskTypeString(),
            ])
        );

        $this->setJsLintRawOutput([
            $this->loadJsLintFixture('incorrect-path-to-node-jslint.txt'),
        ]);

        $this->setExpectedException(
            NodeJslintOutputException::class,
            'node-jslint not found at "/home/example/node_modules/jslint/bin/jslint.js"',
            3
        );

        $this->taskDriver->perform($task);
    }

    /**
     * @dataProvider performDataProvider
     *
     * @param array $httpFixtures
     * @param array $taskParameters
     * @param string[] $jsLintRawOutput
     * @param bool $expectedHasSucceeded
     * @param bool $expectedIsRetryable
     * @param int $expectedErrorCount
     * @param int $expectedWarningCount
     * @param string[] $expectedDecodedOutputKeys
     */
    public function testPerformFoo(
        $httpFixtures,
        $taskParameters,
        $jsLintRawOutput,
        $expectedHasSucceeded,
        $expectedIsRetryable,
        $expectedErrorCount,
        $expectedWarningCount,
        $expectedDecodedOutputKeys
    ) {
        $this->setHttpFixtures($httpFixtures);

        $task = $this->getTaskFactory()->create(
            TaskFactory::createTaskValuesFromDefaults([
                'type' => $this->getTaskTypeString(),
                'parameters' => json_encode($taskParameters),
            ])
        );

        $this->setJsLintRawOutput($jsLintRawOutput);

        $taskDriverResponse = $this->taskDriver->perform($task);
        $decodedTaskOutput = json_decode($taskDriverResponse->getTaskOutput()->getOutput(), true);

        $this->assertEquals($expectedHasSucceeded, $taskDriverResponse->hasSucceeded());
        $this->assertEquals($expectedIsRetryable, $taskDriverResponse->isRetryable());
        $this->assertEquals($expectedErrorCount, $taskDriverResponse->getErrorCount());
        $this->assertEquals($expectedWarningCount, $taskDriverResponse->getWarningCount());
        $this->assertEquals($expectedDecodedOutputKeys, array_keys($decodedTaskOutput));
    }

    /**
     * @return array
     */
    public function performDataProvider()
    {
        return [
            'no js' => [
                'httpFixtures' => [
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                        HtmlDocumentFactory::load('minimal')
                    ),
                ],
                'taskParameters' => [],
                'jsLintRawOutput' => [$this->loadJsLintFixture('no-errors.json')],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
            'script elements, has errors' => [
                'httpFixtures' => [
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                        HtmlDocumentFactory::load('js-script-elements')
                    ),
                    "HTTP/1.1 200 OK\nContent-type:application/javascript\n\n",
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
                    $this->loadJsLintFixture('no-errors.json'),
                    $this->loadJsLintFixture('too-many-errors-stopped-at-seven-percent.json'),
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutputKeys' => [
                    'http://example.com/foo.js',
                    '7dc508faa82075c1039d38c6522c2124',
                ],
            ],
            'redirect impacts script url' => [
                'httpFixtures' => [
                    "HTTP/1.1 301 Moved Permanently\nLocation: http://sub.example.com",
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                        HtmlDocumentFactory::load('js-script-elements')
                    ),
                    "HTTP/1.1 200 OK\nContent-type:application/javascript\n\n",
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
                    $this->loadJsLintFixture('no-errors.json'),
                    $this->loadJsLintFixture('too-many-errors-stopped-at-seven-percent.json'),
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutputKeys' => [
                    'http://sub.example.com/foo.js',
                    '7dc508faa82075c1039d38c6522c2124',
                ],
            ],
            'script elements, stopped' => [
                'httpFixtures' => [
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                        HtmlDocumentFactory::load('js-script-elements')
                    ),
                    "HTTP/1.1 200 OK\nContent-type:application/javascript\n\n",
                ],
                'taskParameters' => [
                    'domains-to-ignore' => [
                        'bar.example.com'
                    ],
                    'jslint-option-predef' => ['window'],
                ],
                'jsLintRawOutput' => [
                    $this->loadJsLintFixture('no-errors.json'),
                    $this->loadJsLintFixture('stopped-no-error.json'),
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutputKeys' => [
                    'http://example.com/foo.js',
                    '7dc508faa82075c1039d38c6522c2124',
                ],
            ],
            'invalid content type exception on linked resource' => [
                'httpFixtures' => [
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                        HtmlDocumentFactory::load('js-script-elements')
                    ),
                    "HTTP/1.1 200 OK\nContent-type:text/html",
                    "HTTP/1.1 200 OK\nContent-type:text/html"
                ],
                'taskParameters' => [
                    'domains-to-ignore' => 'foo',
                ],
                'jsLintRawOutput' => [
                    $this->loadJsLintFixture('no-errors.json'),
                    $this->loadJsLintFixture('no-errors.json'),
                    $this->loadJsLintFixture('no-errors.json'),
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 2,
                'expectedWarningCount' => 0,
                'expectedDecodedOutputKeys' => [
                    'http://example.com/foo.js',
                    'http://bar.example.com/bar.js',
                    '7dc508faa82075c1039d38c6522c2124',
                ],
            ],
            'http 404 getting linked resource' => [
                'httpFixtures' => [
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                        HtmlDocumentFactory::load('js-script-elements')
                    ),
                    "HTTP/1.1 404 Not Found",
                    "HTTP/1.1 404 Not Found",
                ],
                'taskParameters' => [
                    'domains-to-ignore' => [
                        'bar.example.com'
                    ],
                ],
                'jsLintRawOutput' => [
                    $this->loadJsLintFixture('no-errors.json'),
                    $this->loadJsLintFixture('no-errors.json'),
                    $this->loadJsLintFixture('no-errors.json'),
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutputKeys' => [
                    'http://example.com/foo.js',
                    '7dc508faa82075c1039d38c6522c2124',
                ],
            ],
            'http 500 getting linked resource' => [
                'httpFixtures' => [
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                        HtmlDocumentFactory::load('js-script-elements')
                    ),
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                    "HTTP/1.1 500 Internal Server Error",
                ],
                'taskParameters' => [
                    'domains-to-ignore' => [
                        'bar.example.com'
                    ],
                ],
                'jsLintRawOutput' => [
                    $this->loadJsLintFixture('no-errors.json'),
                    $this->loadJsLintFixture('no-errors.json'),
                    $this->loadJsLintFixture('no-errors.json'),
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutputKeys' => [
                    'http://example.com/foo.js',
                    '7dc508faa82075c1039d38c6522c2124',
                ],
            ],
            'curl 6 getting linked resource' => [
                'httpFixtures' => [
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                        HtmlDocumentFactory::load('js-script-elements')
                    ),
                    ConnectExceptionFactory::create('CURL/6 foo'),
                ],
                'taskParameters' => [
                    'domains-to-ignore' => [
                        'bar.example.com'
                    ],
                ],
                'jsLintRawOutput' => [
                    $this->loadJsLintFixture('no-errors.json'),
                    $this->loadJsLintFixture('no-errors.json'),
                    $this->loadJsLintFixture('no-errors.json'),
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutputKeys' => [
                    'http://example.com/foo.js',
                    '7dc508faa82075c1039d38c6522c2124',
                ],
            ],
            'curl 28 getting linked resource' => [
                'httpFixtures' => [
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                        HtmlDocumentFactory::load('js-script-elements')
                    ),
                    ConnectExceptionFactory::create('CURL/28 foo'),
                ],
                'taskParameters' => [
                    'domains-to-ignore' => [
                        'bar.example.com'
                    ],
                ],
                'jsLintRawOutput' => [
                    $this->loadJsLintFixture('no-errors.json'),
                    $this->loadJsLintFixture('no-errors.json'),
                    $this->loadJsLintFixture('no-errors.json'),
                ],
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutputKeys' => [
                    'http://example.com/foo.js',
                    '7dc508faa82075c1039d38c6522c2124',
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
            sprintf(
                "HTTP/1.1 200\nContent-Type:text/html\n\n%s",
                HtmlDocumentFactory::load('empty-body-single-js-link')
            ),
            "HTTP/1.1 200 OK\nContent-type:application/javascript\n\n"
        ]);

        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters)
        ]));

        $this->setJsLintRawOutput([
            $this->loadJsLintFixture('no-errors.json')
        ]);

        $this->taskDriver->perform($task);

        foreach ($this->getHttpClientService()->getHistory()->getRequests(true) as $request) {
            $this->assertEquals($expectedRequestCookieHeader, $request->getHeader('cookie'));
        }
    }

    /**
     * @dataProvider httpAuthDataProvider
     * @inheritdoc
     */
    public function testSetHttpAuthOnHttpClient($taskParameters, $expectedRequestAuthorizationHeaderValue)
    {
        $this->setHttpFixtures([
            sprintf(
                "HTTP/1.1 200\nContent-Type:text/html\n\n%s",
                HtmlDocumentFactory::load('empty-body-single-js-link')
            ),
            "HTTP/1.1 200 OK\nContent-type:application/javascript\n\n"
        ]);

        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters)
        ]));

        $this->setJsLintRawOutput([
            $this->loadJsLintFixture('no-errors.json')
        ]);

        $this->taskDriver->perform($task);

        foreach ($this->getHttpClientService()->getHistory()->getRequests(true) as $request) {
            $decodedAuthorizationHeaderValue = base64_decode(
                str_replace('Basic', '', $request->getHeader('authorization'))
            );

            $this->assertEquals($expectedRequestAuthorizationHeaderValue, $decodedAuthorizationHeaderValue);
        }
    }

    /**
     * @param string[] $fixtures
     */
    protected function setJsLintRawOutput($fixtures)
    {
        PHPMockery::mock(
            'webignition\NodeJslint\Wrapper',
            'shell_exec'
        )->andReturnValues(
            $fixtures
        );
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function loadJsLintFixture($name)
    {
        return file_get_contents(__DIR__ . '/../../Fixtures/Data/RawJsLintOutput/' . $name);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
