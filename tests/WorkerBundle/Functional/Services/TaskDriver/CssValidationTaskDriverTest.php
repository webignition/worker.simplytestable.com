<?php

namespace Tests\WorkerBundle\Functional\Services\TaskDriver;

use Mockery\MockInterface;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Services\TaskDriver\CssValidationTaskDriver;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use Tests\WorkerBundle\Factory\ConnectExceptionFactory;
use Tests\WorkerBundle\Factory\CssValidatorFixtureFactory;
use Tests\WorkerBundle\Factory\HtmlDocumentFactory;
use Tests\WorkerBundle\Factory\TestTaskFactory;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;
use webignition\CssValidatorWrapper\Wrapper as CssValidatorWrapper;
use webignition\CssValidatorWrapper\Configuration\Configuration as CssValidatorWrapperConfiguration;
use webignition\CssValidatorWrapper\Configuration\Flags as CssValidatorWrapperConfigurationFlags;

class CssValidationTaskDriverTest extends WebResourceTaskDriverTest
{
    /**
     * @var CssValidationTaskDriver
     */
    private $taskDriver;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->taskDriver = $this->container->get(CssValidationTaskDriver::class);
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
    public function testPerform(
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

        $task = $this->testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults([
                'type' => $this->getTaskTypeString(),
                'parameters' => json_encode($taskParameters),
            ])
        );

        CssValidatorFixtureFactory::set($cssValidatorOutput);

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
                    "HTTP/1.1 200 OK\nContent-type:text/html",
                    "HTTP/1.1 200 OK\nContent-type:text/html\n\nfoo",
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
                    "HTTP/1.1 200 OK\nContent-type:text/html",
                    "HTTP/1.1 200 OK\nContent-type:text/html\n\nfoo",
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
                    "HTTP/1.1 200 OK\nContent-type:text/html",
                    "HTTP/1.1 200 OK\nContent-type:text/html\n\nfoo",
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
                    "HTTP/1.1 200 OK\nContent-type:text/html",
                    "HTTP/1.1 200 OK\nContent-type:text/html\n\nfoo",
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
                    "HTTP/1.1 200 OK\nContent-type:text/html",
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                        HtmlDocumentFactory::load('empty-body-single-css-link')
                    ),
                    "HTTP/1.1 404 Not Found",
                    "HTTP/1.1 404 Not Found",
                    "HTTP/1.1 404 Not Found",
                    "HTTP/1.1 404 Not Found",
                    "HTTP/1.1 404 Not Found",
                    "HTTP/1.1 404 Not Found",
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
                    "HTTP/1.1 200 OK\nContent-type:text/html",
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
                    "HTTP/1.1 200 OK\nContent-type:text/html",
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                        HtmlDocumentFactory::load('empty-body-single-css-link')
                    ),
                    ConnectExceptionFactory::create('CURL/6 foo')
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
                    "HTTP/1.1 200 OK\nContent-type:text/html",
                    sprintf(
                        "HTTP/1.1 200 OK\nContent-type:text/html\n\n%s",
                        HtmlDocumentFactory::load('empty-body-single-css-link')
                    ),
                    "HTTP/1.1 200 OK\nContent-type:application/pdf"
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
     * @dataProvider performsSetCssValidatorConfigurationDataProvider
     *
     * @param array $taskParameters
     * @param array $expectedConfigurationValues
     */
    public function testPerformSetCssValidatorConfiguration(
        $taskParameters,
        $expectedConfigurationValues
    ) {
        $content = 'foo';

        $this->setHttpFixtures([
            "HTTP/1.1 200 OK\nContent-type:text/html",
            "HTTP/1.1 200 OK\nContent-type:text/html\n\n" . $content,
        ]);

        $task = $this->testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults([
                'type' => $this->getTaskTypeString(),
                'parameters' => json_encode($taskParameters),
            ])
        );

        CssValidatorFixtureFactory::set(CssValidatorFixtureFactory::load('no-messages'));

        /* @var CssValidatorWrapper|MockInterface $cssValidatorWrapper */
        $cssValidatorWrapper = \Mockery::spy(
            $this->container->get(CssValidatorWrapper::class)
        );

        $this->getTaskDriver()->setCssValidatorWrapper($cssValidatorWrapper);

        $this->taskDriver->perform($task);

        $standardConfigurationValues = [
            CssValidatorWrapperConfiguration::CONFIG_KEY_CSS_VALIDATOR_JAR_PATH =>
                $this->container->getParameter('css-validator-jar-path'),
            CssValidatorWrapperConfiguration::CONFIG_KEY_URL_TO_VALIDATE =>
                'http://example.com/',
            CssValidatorWrapperConfiguration::CONFIG_KEY_CONTENT_TO_VALIDATE =>
                $content,
            CssValidatorWrapperConfiguration::CONFIG_KEY_HTTP_CLIENT =>
                $this->container->get(HttpClientService::class)->get(),
        ];

        $cssValidatorWrapper
            ->shouldHaveReceived('createConfiguration')
            ->once()
            ->with(array_merge($expectedConfigurationValues, $standardConfigurationValues));
    }

    /**
     * @return array
     */
    public function performsSetCssValidatorConfigurationDataProvider()
    {
        return [
            'default' => [
                'taskParameters' => [],
                'expectedConfigurationValues' => [
                    CssValidatorWrapperConfiguration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL =>
                        VendorExtensionSeverityLevel::LEVEL_WARN,
                    CssValidatorWrapperConfiguration::CONFIG_KEY_DOMAINS_TO_IGNORE => [],
                    CssValidatorWrapperConfiguration::CONFIG_KEY_FLAGS => [
                        CssValidatorWrapperConfigurationFlags::FLAG_IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES,
                    ],
                ],
            ],
            'non-default' => [
                'taskParameters' => [
                    'vendor-extensions' => VendorExtensionSeverityLevel::LEVEL_ERROR,
                    'domains-to-ignore' => [
                        'foo',
                        'bar',
                    ],
                    'ignore-warnings' => true,
                ],
                'expectedConfigurationValues' => [
                    CssValidatorWrapperConfiguration::CONFIG_KEY_FLAGS => [
                        CssValidatorWrapperConfigurationFlags::FLAG_IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES,
                        CssValidatorWrapperConfigurationFlags::FLAG_IGNORE_WARNINGS,
                    ],
                    CssValidatorWrapperConfiguration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL =>
                        VendorExtensionSeverityLevel::LEVEL_ERROR,
                    CssValidatorWrapperConfiguration::CONFIG_KEY_DOMAINS_TO_IGNORE => [
                        'foo',
                        'bar',
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
    public function testSetCookiesOnHttpClient($taskParameters, $expectedRequestCookieHeader)
    {
        $this->setHttpFixtures([
            sprintf(
                "HTTP/1.1 200\nContent-Type:text/html\n\n%s",
                HtmlDocumentFactory::load('empty-body-single-css-link')
            ),
            "HTTP/1.1 200 OK\nContent-type:text/css\n\n"
        ]);

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters)
        ]));

        CssValidatorFixtureFactory::set(CssValidatorFixtureFactory::load('no-messages'));

        $this->taskDriver->perform($task);

        foreach ($this->container->get(HttpClientService::class)->getHistory()->getRequests(true) as $request) {
            $this->assertEquals($expectedRequestCookieHeader, $request->getHeader('cookie'));
        }
    }

    /**
     * @dataProvider httpAuthDataProvider
     *
     * {@inheritdoc}
     */
    public function testSetHttpAuthOnHttpClient($taskParameters, $expectedRequestAuthorizationHeaderValue)
    {
        $this->setHttpFixtures([
            sprintf(
                "HTTP/1.1 200\nContent-Type:text/html\n\n%s",
                HtmlDocumentFactory::load('empty-body-single-css-link')
            ),
            "HTTP/1.1 200 OK\nContent-type:text-css\n\n"
        ]);

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters)
        ]));

        CssValidatorFixtureFactory::set(CssValidatorFixtureFactory::load('no-messages'));

        $this->taskDriver->perform($task);

        foreach ($this->container->get(HttpClientService::class)->getHistory()->getRequests(true) as $request) {
            $decodedAuthorizationHeaderValue = base64_decode(
                str_replace('Basic', '', $request->getHeader('authorization'))
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
