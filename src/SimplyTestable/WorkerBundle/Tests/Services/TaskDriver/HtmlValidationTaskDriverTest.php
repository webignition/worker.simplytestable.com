<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Services\TaskDriver\HtmlValidationTaskDriver;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use SimplyTestable\WorkerBundle\Tests\Factory\HtmlValidatorOutputFactory;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;
use webignition\HtmlValidator\Output\Output as HtmlValidatorOutput;
use webignition\HtmlValidator\Wrapper\Wrapper as HtmlValidatorWrapper;

/**
 * Class HtmlValidationTaskDriverTest
 * @package SimplyTestable\WorkerBundle\Tests\Services\TaskDriver
 *
 * @group foo-tests
 */
class HtmlValidationTaskDriverTest extends FooWebResourceTaskDriverTest
{
    /**
     * @var HtmlValidationTaskDriver
     */
    private $taskDriver;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->taskDriver = $this->container->get('simplytestable.services.taskdriver.htmlvalidation');
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
        return strtolower(TaskTypeService::HTML_VALIDATION_NAME);
    }

    /**
     * @dataProvider badDocumentTypeDataProvider
     *
     * @param string $content
     * @param array $expectedOutputMessage
     */
    public function testPerformBadDocumentType($content, $expectedOutputMessage)
    {
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200 OK\nContent-Type:text/html\n\n" . $content
        )));

        $task = $this->getTaskFactory()->create(
            TaskFactory::createTaskValuesFromDefaults()
        );

        $taskDriverResponse = $this->taskDriver->perform($task);

        $this->assertEquals(1, $taskDriverResponse->getErrorCount());
        $this->assertEquals([
            'messages' => [
                $expectedOutputMessage,
            ],
        ], json_decode($taskDriverResponse->getTaskOutput()->getOutput(), true));
    }

    /**
     * @return array
     */
    public function badDocumentTypeDataProvider()
    {
        return [
            'not markup' => [
                'content' => 'foo',
                'expectedOutputMessage' => [
                    'message' => 'Not markup',
                    'messageId' => 'document-is-not-markup',
                    'type' => 'error',
                    'fragment' => 'foo',
                ],
            ],
            'missing document type' => [
                'content' => '<html>',
                'expectedOutputMessage' => [
                    'message' => 'No doctype',
                    'messageId' => 'document-type-missing',
                    'type' => 'error',
                ],
            ],
            'invalid document type' => [
                'content' => '<!doctype foo><html>',
                'expectedOutputMessage' => [
                    'message' => '<!doctype foo>',
                    'messageId' => 'document-type-invalid',
                    'type' => 'error',
                ],
            ]
        ];
    }

    /**
     * @dataProvider performDataProvider
     *
     * @param string $content
     * @param HtmlValidatorOutput $htmlValidatorOutput
     * @param bool $expectedHasSucceeded
     * @param bool $expectedIsRetryable
     */
    public function testPerform(
        $content,
        HtmlValidatorOutput $htmlValidatorOutput,
        $expectedHasSucceeded,
        $expectedIsRetryable
    ) {
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200\nContent-Type:text/html\n\n" . $content
        )));

        $task = $this->getTaskFactory()->create(
            TaskFactory::createTaskValuesFromDefaults()
        );

        $htmlValidatorWrapper = \Mockery::mock(HtmlValidatorWrapper::class);
        $htmlValidatorWrapper
            ->shouldReceive('createConfiguration')
            ->with(array(
                'documentUri' => 'file:/tmp/fe364450e1391215f596d043488f989f.html',
                'validatorPath' => '/usr/local/validator/cgi-bin/check',
                'documentCharacterSet' => 'UTF-8',
            ));

        $htmlValidatorWrapper
            ->shouldReceive('validate')
            ->andReturn($htmlValidatorOutput);

        $this->taskDriver->setHtmlValidatorWrapper($htmlValidatorWrapper);

        $taskDriverResponse = $this->taskDriver->perform($task);

        $this->assertEquals($expectedHasSucceeded, $taskDriverResponse->hasSucceeded());
        $this->assertEquals($expectedIsRetryable, $taskDriverResponse->isRetryable());
    }

    /**
     * @return array
     */
    public function performDataProvider()
    {
        return [
            'no errors' => [
                'content' => '<!DOCTYPE html>',
                'htmlValidatorOutput' => HtmlValidatorOutputFactory::create(
                    HtmlValidatorOutput::STATUS_VALID
                ),
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
            ],
            'was aborted' => [
                'content' => '<!DOCTYPE html>',
                'htmlValidatorOutput' => HtmlValidatorOutputFactory::create(
                    HtmlValidatorOutput::STATUS_ABORT
                ),
                 'expectedHasSucceeded' => false,
                'expectedIsRetryable' => false,
            ],
        ];
    }

    /**
     * @dataProvider cookiesDataProvider
     *
     * @param $taskValues
     * @param $expectedRequestCookieHeader
     */
    public function testSetCookiesOnHttpClient($taskValues, $expectedRequestCookieHeader)
    {
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200\nContent-Type:text/html\n\n<!doctype html>"
        )));

        $task = $this->getTaskFactory()->create($taskValues);

        $htmlValidatorWrapper = \Mockery::mock(HtmlValidatorWrapper::class);
        $htmlValidatorWrapper
            ->shouldReceive('createConfiguration');

        $htmlValidatorWrapper
            ->shouldReceive('validate')
            ->andReturn(HtmlValidatorOutputFactory::create(
                HtmlValidatorOutput::STATUS_VALID
            ));

        $this->taskDriver->setHtmlValidatorWrapper($htmlValidatorWrapper);

        $this->taskDriver->perform($task);

        $request = $this->getHttpClientService()->getHistory()->getLastRequest();
        $this->assertEquals($expectedRequestCookieHeader, $request->getHeader('cookie'));
    }

    /**
     * @return array
     */
    public function cookiesDataProvider()
    {
        return [
            'no cookies' => [
                'taskValues' => TaskFactory::createTaskValuesFromDefaults([
                    'parameters' => json_encode([]),
                ]),
                'expectedRequestCookieHeader' => '',
            ],
            'single cookie' => [
                'taskValues' => TaskFactory::createTaskValuesFromDefaults([
                    'parameters' => json_encode([
                        'cookies' => [
                            [
                                'Name' => 'foo',
                                'Value' => 'bar',
                                'Domain' => '.example.com',
                            ],
                        ],
                    ]),
                ]),
                'expectedRequestCookieHeader' => 'foo=bar',
            ],
            'multiple cookies' => [
                'taskValues' => TaskFactory::createTaskValuesFromDefaults([
                    'parameters' => json_encode([
                        'cookies' => [
                            [
                                'Name' => 'foo1',
                                'Value' => 'bar1',
                                'Domain' => '.example.com',
                            ],
                            [
                                'Name' => 'foo2',
                                'Value' => 'bar2',
                                'Domain' => '.example.com',
                            ],
                            [
                                'Name' => 'foo3',
                                'Value' => 'bar3',
                                'Domain' => '.example.com',
                            ],
                        ],
                    ]),
                ]),
                'expectedRequestCookieHeader' => 'foo1=bar1; foo2=bar2; foo3=bar3',
            ],
        ];
    }

    /**
     * @dataProvider httpAuthDataProvider
     *
     * @param array $taskValues
     * @param string $expectedRequestAuthorizationHeaderValue
     */
    public function testSetHttpAuthOnHttpClient($taskValues, $expectedRequestAuthorizationHeaderValue)
    {
        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200\nContent-Type:text/html\n\n<!doctype html>"
        )));

        $task = $this->getTaskFactory()->create($taskValues);

        $htmlValidatorWrapper = \Mockery::mock(HtmlValidatorWrapper::class);
        $htmlValidatorWrapper
            ->shouldReceive('createConfiguration');

        $htmlValidatorWrapper
            ->shouldReceive('validate')
            ->andReturn(HtmlValidatorOutputFactory::create(
                HtmlValidatorOutput::STATUS_VALID
            ));

        $this->taskDriver->setHtmlValidatorWrapper($htmlValidatorWrapper);

        $this->taskDriver->perform($task);

        $request = $this->getHttpClientService()->getHistory()->getLastRequest();

        $decodedAuthorizationHeaderValue = base64_decode(
            str_replace('Basic', '', $request->getHeader('authorization'))
        );

        $this->assertEquals($expectedRequestAuthorizationHeaderValue, $decodedAuthorizationHeaderValue);
    }

    /**
     * @return array
     */
    public function httpAuthDataProvider()
    {
        return [
            'no auth' => [
                'taskValues' => TaskFactory::createTaskValuesFromDefaults([
                    'parameters' => json_encode([]),
                ]),
                'expectedRequestAuthorizationHeaderValue' => '',
            ],
            'has auth' => [
                'taskValues' => TaskFactory::createTaskValuesFromDefaults([
                    'parameters' => json_encode([
                        'http-auth-username' => 'foouser',
                        'http-auth-password' => 'foopassword',
                    ]),
                ]),
                'expectedRequestAuthorizationHeaderValue' => 'foouser:foopassword',
            ],
        ];
    }

    /**
     * @dataProvider storeTmpFileDataProvider
     *
     * @param $fileExists
     */
    public function testStoreTmpFile($fileExists)
    {
        $tmpFilePath = sys_get_temp_dir() . '/f45451f4d07ca1f5bab9ed278e880c5f.html';
        $content = '<!doctype html>';

        if (!$fileExists) {
            unlink($tmpFilePath);
        } else {
            file_put_contents($tmpFilePath, $content);
        }

        $this->setHttpFixtures($this->buildHttpFixtureSet(array(
            "HTTP/1.0 200\nContent-Type:text/html\n\n" . $content
        )));

        $task = $this->getTaskFactory()->create(
            TaskFactory::createTaskValuesFromDefaults()
        );

        $htmlValidatorWrapper = \Mockery::mock(HtmlValidatorWrapper::class);
        $htmlValidatorWrapper
            ->shouldReceive('createConfiguration');

        $htmlValidatorOutput = HtmlValidatorOutputFactory::create(
            HtmlValidatorOutput::STATUS_VALID
        );

        $htmlValidatorWrapper
            ->shouldReceive('validate')
            ->andReturn($htmlValidatorOutput);

        $this->taskDriver->setHtmlValidatorWrapper($htmlValidatorWrapper);

        $this->taskDriver->perform($task);
    }

    /**
     * @return array
     */
    public function storeTmpFileDataProvider()
    {
        return [
            'tmp file does not already exist' => [
                'fileExists' => false,
            ],
            'tmp file already exists' => [
                'fileExists' => true,
            ],
        ];
    }
}
