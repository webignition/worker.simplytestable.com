<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Services\TaskDriver\HtmlValidationTaskDriver;
use SimplyTestable\WorkerBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\WorkerBundle\Tests\Factory\HtmlValidatorOutputFactory;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;
use webignition\HtmlValidator\Output\Output as HtmlValidatorOutput;
use webignition\HtmlValidator\Wrapper\Wrapper as HtmlValidatorWrapper;

class HtmlValidationTaskDriverTest extends BaseSimplyTestableTestCase
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->clearMemcacheHttpCache();
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

        $taskDriverResponse = $this->getHtmlValidationTaskDriver()->perform($task);

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
     * @dataProvider performBadWebResourceDataProvider
     *
     * @param string[] $httpResponseFixtures
     * @param bool $expectedWebResourceRetrievalHasSucceeded
     * @param bool $expectedIsRetryable
     * @param int $expectedErrorCount
     * @param string $expectedTaskOutput
     */
    public function testPerformBadWebResource(
        $httpResponseFixtures,
        $expectedWebResourceRetrievalHasSucceeded,
        $expectedIsRetryable,
        $expectedErrorCount,
        $expectedTaskOutput
    ) {
        $this->setHttpFixtures($this->buildHttpFixtureSet($httpResponseFixtures));

        $task = $this->getTaskFactory()->create(
            TaskFactory::createTaskValuesFromDefaults()
        );

        $htmlValidationTaskDriver = $this->getHtmlValidationTaskDriver();

        $taskDriverResponse = $htmlValidationTaskDriver->perform($task);

        $this->assertEquals($expectedWebResourceRetrievalHasSucceeded, $taskDriverResponse->hasSucceeded());
        $this->assertEquals($expectedIsRetryable, $taskDriverResponse->isRetryable());
        $this->assertEquals($expectedErrorCount, $taskDriverResponse->getErrorCount());

        $this->assertEquals(
            $expectedTaskOutput,
            json_decode($taskDriverResponse->getTaskOutput()->getOutput(), true)
        );
    }

    public function performBadWebResourceDataProvider()
    {
        return [
            'http too many redirects' => [
                'httpResponseFixtures' => [
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TaskFactory::DEFAULT_TASK_URL . "1",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TaskFactory::DEFAULT_TASK_URL . "2",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TaskFactory::DEFAULT_TASK_URL . "3",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TaskFactory::DEFAULT_TASK_URL . "4",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TaskFactory::DEFAULT_TASK_URL . "5",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TaskFactory::DEFAULT_TASK_URL . "6",
                ],
                'expectedWebResourceRetrievalHasSucceeded' => false,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 1,
                'expectedTaskOutput' => [
                    'messages' => [
                        [
                            'message' => 'Redirect limit reached',
                            'messageId' => 'http-retrieval-redirect-limit-reached',
                            'type' => 'error',
                        ],
                    ],
                ],
            ],
            'http redirect loop' => [
                'httpResponseFixtures' => [
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TaskFactory::DEFAULT_TASK_URL . "1",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TaskFactory::DEFAULT_TASK_URL . "2",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TaskFactory::DEFAULT_TASK_URL . "3",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TaskFactory::DEFAULT_TASK_URL,
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TaskFactory::DEFAULT_TASK_URL . "1",
                    "HTTP/1.1 301 Moved Permanently\nLocation: " . TaskFactory::DEFAULT_TASK_URL . "2",
                ],
                'expectedWebResourceRetrievalHasSucceeded' => false,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 1,
                'expectedTaskOutput' => [
                    'messages' => [
                        [
                            'message' => 'Redirect loop detected',
                            'messageId' => 'http-retrieval-redirect-loop',
                            'type' => 'error',
                        ],
                    ],
                ],
            ],
            'http 404' => [
                'httpResponseFixtures' => [
                    'HTTP/1.1 404 Not Found',
                    'HTTP/1.1 404 Not Found',
                ],
                'expectedWebResourceRetrievalHasSucceeded' => false,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 1,
                'expectedTaskOutput' => [
                    'messages' => [
                        [
                            'message' => 'Not Found',
                            'messageId' => 'http-retrieval-404',
                            'type' => 'error',
                        ],
                    ],
                ]
            ],
            'curl 6' => [
                'httpResponseFixtures' => [
                    'CURL/28: Operation timed out.',
                ],
                'expectedWebResourceRetrievalHasSucceeded' => false,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 1,
                'expectedTaskOutput' => [
                    'messages' => [
                        [
                            'message' => 'Timeout reached retrieving resource',
                            'messageId' => 'http-retrieval-curl-code-28',
                            'type' => 'error',
                        ],
                    ],
                ]
            ],
            'incorrect resource type' => [
                'httpResponseFixtures' => [
                    "HTTP/1.1 200 OK\nContent-type:text/css\n\nfoo",
                ],
                'expectedWebResourceRetrievalHasSucceeded' => true,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 0,
                'expectedTaskOutput' =>
                    null
            ],
            'empty content' => [
                'httpResponseFixtures' => [
                    "HTTP/1.1 200 OK\nContent-type:text/html",
                ],
                'expectedWebResourceRetrievalHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 0,
                'expectedTaskOutput' =>
                    null
            ],
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

        $htmlValidationTaskDriver = $this->getHtmlValidationTaskDriver();
        $htmlValidationTaskDriver->setHtmlValidatorWrapper($htmlValidatorWrapper);

        $taskDriverResponse = $htmlValidationTaskDriver->perform($task);

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

    public function test

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

        $htmlValidationTaskDriver = $this->getHtmlValidationTaskDriver();
        $htmlValidationTaskDriver->setHtmlValidatorWrapper($htmlValidatorWrapper);

        $htmlValidationTaskDriver->perform($task);
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

    /**
     * @return HtmlValidationTaskDriver
     */
    private function getHtmlValidationTaskDriver()
    {
        /* @var $htmlValidationTaskDriver HtmlValidationTaskDriver */
        $htmlValidationTaskDriver = $this->container->get('simplytestable.services.taskdriver.htmlvalidation');

        return $htmlValidationTaskDriver;
    }
}
