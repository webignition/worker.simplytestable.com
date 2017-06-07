<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver;

use phpmock\mockery\PHPMockery;
use SimplyTestable\WorkerBundle\Services\TaskDriver\CssValidationTaskDriver;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use SimplyTestable\WorkerBundle\Tests\Factory\ConnectExceptionFactory;
use SimplyTestable\WorkerBundle\Tests\Factory\HtmlDocumentFactory;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;

/**
 * Class CssValidationTaskDriverTest
 * @package SimplyTestable\WorkerBundle\Tests\Services\TaskDriver
 *
 * @group foo-tests
 */
class CssValidationTaskDriverTest extends FooWebResourceTaskDriverTest
{
    /**
     * @var CssValidationTaskDriver
     */
    private $taskDriver;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->taskDriver = $this->container->get('simplytestable.services.taskdriver.cssvalidation');
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
        return strtolower(TaskTypeService::CSS_VALIDATION_NAME);
    }

    /**
     * @dataProvider performDataProvider
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
    public function testPerformFoo(
        $httpFixtures,
        $taskParameters,
        $cssValidatorOutput,
        $expectedHasSucceeded,
        $expectedIsRetryable,
        $expectedErrorCount,
        $expectedWarningCount,
        $expectedDecodedOutput
    ) {
        $this->setHttpFixtures($httpFixtures);

        $task = $this->getTaskFactory()->create(
            TaskFactory::createTaskValuesFromDefaults([
                'type' => $this->getTaskTypeString(),
                'parameters' => json_encode($taskParameters),
            ])
        );

        $this->setCssValidatorRawOutput($cssValidatorOutput);

        $taskDriverResponse = $this->taskDriver->perform($task);

        $this->assertEquals($expectedHasSucceeded, $taskDriverResponse->hasSucceeded());
        $this->assertEquals($expectedIsRetryable, $taskDriverResponse->isRetryable());
        $this->assertEquals($expectedErrorCount, $taskDriverResponse->getErrorCount());
        $this->assertEquals($expectedWarningCount, $taskDriverResponse->getWarningCount());
        $this->assertEquals($expectedDecodedOutput, json_decode($taskDriverResponse->getTaskOutput()->getOutput()));
    }

    /**
     * @return array
     */
    public function performDataProvider()
    {
        return [
            'unknown validator exception' => [
                'httpFixtures' => [
                    "HTTP/1.1 200 OK\nContent-type:text/html\n\nfoo",
                ],
                'taskParameters' => [],
                'cssValidatorOutput' => $this->loadCssValidatorFixture('unknown-exception'),
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
                    "HTTP/1.1 200 OK\nContent-type:text/html\n\nfoo",
                ],
                'taskParameters' => [
                    'ignore-warnings' => true,
                ],
                'cssValidatorOutput' => $this->loadCssValidatorFixture('1-vendor-extension-warning'),
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
            'three errors' => [
                'httpFixtures' => [
                    "HTTP/1.1 200 OK\nContent-type:text/html\n\nfoo",
                ],
                'taskParameters' => [
                    'ignore-warnings' => true,
                ],
                'cssValidatorOutput' => $this->loadCssValidatorFixture('3-errors'),
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
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                        HtmlDocumentFactory::load('empty-body-single-css-link')
                    ),
                    "HTTP/1.1 404 Not Found",
                    "HTTP/1.1 404 Not Found",
                ],
                'taskParameters' => [],
                'cssValidatorOutput' => $this->loadCssValidatorFixture('no-messages'),
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
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                        HtmlDocumentFactory::load('empty-body-single-css-link')
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
                'taskParameters' => [],
                'cssValidatorOutput' => $this->loadCssValidatorFixture('no-messages'),
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
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                        HtmlDocumentFactory::load('empty-body-single-css-link')
                    ),
                    ConnectExceptionFactory::create('CURL/6 foo')
                ],
                'taskParameters' => [],
                'cssValidatorOutput' => $this->loadCssValidatorFixture('no-messages'),
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
        ];
    }

    /**
     * @dataProvider cookiesDataProvider
     * @inheritdoc
     */
    public function testSetCookiesOnHttpClient($taskParameters, $expectedRequestCookieHeader)
    {
        $this->setHttpFixtures(array(
            sprintf(
                "HTTP/1.1 200\nContent-Type:text/html\n\n%s",
                HtmlDocumentFactory::load('empty-body-single-css-link')
            ),
            "HTTP/1.1 200 OK\nContent-type:text-css\n\n"
        ));

        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([
            'type' => 'css validation',
            'parameters' => json_encode($taskParameters)
        ]));

        $this->setCssValidatorRawOutput($this->loadCssValidatorFixture('no-messages'));

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
        $this->setHttpFixtures(array(
            sprintf(
                "HTTP/1.1 200\nContent-Type:text/html\n\n%s",
                HtmlDocumentFactory::load('empty-body-single-css-link')
            ),
            "HTTP/1.1 200 OK\nContent-type:text-css\n\n"
        ));

        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([
            'type' => 'css validation',
            'parameters' => json_encode($taskParameters)
        ]));

        $this->setCssValidatorRawOutput($this->loadCssValidatorFixture('no-messages'));

        $this->taskDriver->perform($task);

        foreach ($this->getHttpClientService()->getHistory()->getRequests(true) as $request) {
            $decodedAuthorizationHeaderValue = base64_decode(
                str_replace('Basic', '', $request->getHeader('authorization'))
            );

            $this->assertEquals($expectedRequestAuthorizationHeaderValue, $decodedAuthorizationHeaderValue);
        }
    }

    /**
     * @param string $rawOutput
     */
    private function setCssValidatorRawOutput($rawOutput)
    {
        PHPMockery::mock(
            'webignition\CssValidatorWrapper',
            'shell_exec'
        )->andReturn(
            $rawOutput
        );
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function loadCssValidatorFixture($name)
    {
        return file_get_contents(__DIR__ . '/../../Fixtures/Data/RawCssValidatorOutput/' . $name . '.txt');
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
