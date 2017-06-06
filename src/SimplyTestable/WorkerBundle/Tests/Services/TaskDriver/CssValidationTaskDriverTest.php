<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver;

use Mockery\MockInterface;
use SimplyTestable\WorkerBundle\Services\TaskDriver\CssValidationTaskDriver;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;
use webignition\CssValidatorOutput\CssValidatorOutput;
use webignition\CssValidatorOutput\Message\Error as CssValidatorOutputError;
use webignition\CssValidatorWrapper\Configuration\Configuration as CssValidatorWrapperConfiguration;
use webignition\CssValidatorWrapper\Configuration\Flags as CssValidatorWrapperConfigurationFlags;
use webignition\CssValidatorWrapper\Configuration\VendorExtensionSeverityLevel;
use webignition\CssValidatorWrapper\Wrapper as CssValidatorWrapper;
use webignition\WebResource\Service\Configuration as WebResourceServiceConfiguration;
use webignition\WebResource\Service\Service as WebResourceService;

/**
 * Class CssValidationTaskDriverTest
 * @package SimplyTestable\WorkerBundle\Tests\Services\TaskDriver
 *
 * @group foo-tests
 */
class CssValidationTaskDriverTest extends FooWebResourceTaskDriverTest
{
    /**
     * @inheritdoc
     */
    protected function getTaskDriver()
    {
        return $this->getCssValidationTaskDriver();
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
     * @param array $expectedAdditionalCreateConfigurationArgs
     * @param CssValidatorOutput $cssValidatorOutput
     * @param bool $expectedHasSucceeded
     * @param bool $expectedIsRetryable
     * @param int $expectedErrorCount
     * @param int $expectedWarningCount
     * @param array $expectedDecodedOutput
     */
    public function testPerformFoo(
        $httpFixtures,
        $taskParameters,
        $expectedAdditionalCreateConfigurationArgs,
        CssValidatorOutput $cssValidatorOutput,
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

        $cssValidatorWrapper = \Mockery::mock(CssValidatorWrapper::class);

        $foo = [
            CssValidatorWrapperConfiguration::CONFIG_KEY_CSS_VALIDATOR_JAR_PATH =>
                $this->container->getParameter('css-validator-jar-path'),
            CssValidatorWrapperConfiguration::CONFIG_KEY_URL_TO_VALIDATE => 'http://example.com/',
            CssValidatorWrapperConfiguration::CONFIG_KEY_HTTP_CLIENT => $this->getHttpClientService()->get(),
        ];

        $cssValidatorWrapper
            ->shouldReceive('createConfiguration')
            ->with(array_merge($expectedAdditionalCreateConfigurationArgs, $foo));

        $this->createEnableRetryWithUrlEncodingDisabledExpectations($cssValidatorWrapper);

        $cssValidatorWrapper
            ->shouldReceive('validate')
            ->andReturn($cssValidatorOutput);

        $cssValidationTaskDriver = $this->getCssValidationTaskDriver();
        $cssValidationTaskDriver->setCssValidatorWrapper($cssValidatorWrapper);

        $taskDriverResponse = $cssValidationTaskDriver->perform($task);

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
                'expectedAdditionalCreateConfigurationArgs' => [
                    CssValidatorWrapperConfiguration::CONFIG_KEY_DOMAINS_TO_IGNORE => [],
                    CssValidatorWrapperConfiguration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL =>
                        VendorExtensionSeverityLevel::LEVEL_WARN,
                    CssValidatorWrapperConfiguration::CONFIG_KEY_FLAGS => [
                        CssValidatorWrapperConfigurationFlags::FLAG_IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES,
                    ],
                ],
                'cssValidatorOutput' => $this->createCssValidatorOutput(
                    true,
                    1,
                    0,
                    []
                ),
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
                'expectedAdditionalCreateConfigurationArgs' => [
                    CssValidatorWrapperConfiguration::CONFIG_KEY_DOMAINS_TO_IGNORE => [],
                    CssValidatorWrapperConfiguration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL =>
                        VendorExtensionSeverityLevel::LEVEL_WARN,
                    CssValidatorWrapperConfiguration::CONFIG_KEY_FLAGS => [
                        CssValidatorWrapperConfigurationFlags::FLAG_IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES,
                        CssValidatorWrapperConfigurationFlags::FLAG_IGNORE_WARNINGS,
                    ],
                ],
                'cssValidatorOutput' => $this->createCssValidatorOutput(
                    false,
                    0,
                    0,
                    []
                ),
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 0,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [],
            ],
            'css validator http error' => [
                'httpFixtures' => [
                    "HTTP/1.1 200 OK\nContent-type:text/html\n\nfoo",
                ],
                'taskParameters' => [],
                'expectedAdditionalCreateConfigurationArgs' => [
                    CssValidatorWrapperConfiguration::CONFIG_KEY_DOMAINS_TO_IGNORE => [],
                    CssValidatorWrapperConfiguration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL =>
                        VendorExtensionSeverityLevel::LEVEL_WARN,
                    CssValidatorWrapperConfiguration::CONFIG_KEY_FLAGS => [
                        CssValidatorWrapperConfigurationFlags::FLAG_IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES,
                    ],
                ],
                'cssValidatorOutput' => $this->createCssValidatorOutput(
                    false,
                    1,
                    0,
                    [
                        $this->createCssValidatorOutputError(
                            'http-error:500',
                            'context',
                            'ref',
                            2
                        ),
                    ]
                ),
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    (object)[
                        'message' => 'http-retrieval-500',
                        'type' => 'error',
                        'context' => 'context',
                        'ref' => 'ref',
                        'line_number' => 2,
                    ],
                ],
            ],
            'css validator curl error' => [
                'httpFixtures' => [
                    "HTTP/1.1 200 OK\nContent-type:text/html\n\nfoo",
                ],
                'taskParameters' => [],
                'expectedAdditionalCreateConfigurationArgs' => [
                    CssValidatorWrapperConfiguration::CONFIG_KEY_DOMAINS_TO_IGNORE => [],
                    CssValidatorWrapperConfiguration::CONFIG_KEY_VENDOR_EXTENSION_SEVERITY_LEVEL =>
                        VendorExtensionSeverityLevel::LEVEL_WARN,
                    CssValidatorWrapperConfiguration::CONFIG_KEY_FLAGS => [
                        CssValidatorWrapperConfigurationFlags::FLAG_IGNORE_FALSE_IMAGE_DATA_URL_MESSAGES,
                    ],
                ],
                'cssValidatorOutput' => $this->createCssValidatorOutput(
                    false,
                    1,
                    0,
                    [
                        $this->createCssValidatorOutputError(
                            'curl-error:28',
                            'context',
                            'ref',
                            2
                        ),
                    ]
                ),
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedWarningCount' => 0,
                'expectedDecodedOutput' => [
                    (object)[
                        'message' => 'http-retrieval-curl-code-28',
                        'type' => 'error',
                        'context' => 'context',
                        'ref' => 'ref',
                        'line_number' => 2,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return CssValidationTaskDriver
     */
    private function getCssValidationTaskDriver()
    {
        /* @var $cssValidationTaskDriver CssValidationTaskDriver */
        $cssValidationTaskDriver = $this->container->get('simplytestable.services.taskdriver.cssvalidation');

        return $cssValidationTaskDriver;
    }

    /**
     * @param MockInterface $cssValidatorWrapper
     */
    private function createEnableRetryWithUrlEncodingDisabledExpectations(MockInterface $cssValidatorWrapper)
    {
        $webResourceServiceConfiguration = \Mockery::mock(WebResourceServiceConfiguration::class);
        $webResourceServiceConfiguration
            ->shouldReceive('enableRetryWithUrlEncodingDisabled')
            ->once()
            ->withNoArgs();

        $webResourceService = \Mockery::mock(WebResourceService::class);
        $webResourceService
            ->shouldReceive('getConfiguration')
            ->once()
            ->withNoArgs()
            ->andReturn($webResourceServiceConfiguration);

        $cssValidatorWrapperConfiguration = \Mockery::mock(CssValidatorWrapperConfiguration::class);
        $cssValidatorWrapperConfiguration
            ->shouldReceive('getWebResourceService')
            ->once()
            ->withNoArgs()
            ->andReturn($webResourceService);

        $cssValidatorWrapper
            ->shouldReceive('getConfiguration')
            ->once()
            ->withNoArgs()
            ->andReturn($cssValidatorWrapperConfiguration);
    }

    /**
     * @return MockInterface|CssValidatorOutput
     */
    private function createCssValidatorOutput($hasException, $errorCount, $warningCount, $messages)
    {
        $output = \Mockery::mock(CssValidatorOutput::class);
        $output
            ->shouldReceive('hasException')
            ->once()
            ->withNoArgs()
            ->andReturn($hasException);

        $output
            ->shouldReceive('getErrorCount')
            ->once()
            ->withNoArgs()
            ->andReturn($errorCount);

        $output
            ->shouldReceive('getWarningCount')
            ->once()
            ->withNoArgs()
            ->andReturn($warningCount);

        $output
            ->shouldReceive('getMessages')
            ->once()
            ->withNoArgs()
            ->andReturn($messages);

        return $output;
    }

    /**
     * @return CssValidatorOutputError
     */
    private function createCssValidatorOutputError($message, $context, $ref, $lineNumber)
    {
        $error = new CssValidatorOutputError();
        $error->setMessage($message);
        $error->setContext($context);
        $error->setRef($ref);
        $error->setLineNumber($lineNumber);

        return $error;
    }
}
