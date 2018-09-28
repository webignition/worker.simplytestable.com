<?php

namespace App\Tests\Functional\Services\TaskDriver;

use App\Model\Task\TypeInterface;
use GuzzleHttp\Psr7\Response;
use App\Services\TaskDriver\HtmlValidationTaskDriver;
use App\Tests\Factory\HtmlValidatorFixtureFactory;
use App\Tests\Factory\TestTaskFactory;

class HtmlValidationTaskDriverTest extends AbstractWebPageTaskDriverTest
{
    /**
     * @var HtmlValidationTaskDriver
     */
    private $taskDriver;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->taskDriver = self::$container->get(HtmlValidationTaskDriver::class);
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
        return TypeInterface::TYPE_HTML_VALIDATION;
    }

    /**
     * @dataProvider badDocumentTypeDataProvider
     *
     * @param string $content
     * @param array $expectedOutputMessage
     */
    public function testPerformBadDocumentType($content, $expectedOutputMessage)
    {
        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], $content),
        ]);

        $task = $this->testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults()
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
            'unknown document type' => [
                'content' => '<!DOCTYPE html PUBLIC><html>',
                'expectedOutputMessage' => [
                    'message' => '<!DOCTYPE html PUBLIC>',
                    'messageId' => 'document-type-invalid',
                    'type' => 'error',
                ],
            ],
            'invalid document type; no uri' => [
                'content' => '<!DOCTYPE html PUBLIC "foo"><html>',
                'expectedOutputMessage' => [
                    'message' => '<!DOCTYPE html PUBLIC "foo">',
                    'messageId' => 'document-type-invalid',
                    'type' => 'error',
                ],
            ],
            'invalid document type; fpi and uri' => [
                'content' => '<!DOCTYPE html PUBLIC "foo" "http://www.w3.org/TR/html4/foo.dtd"><html>',
                'expectedOutputMessage' => [
                    'message' => '<!DOCTYPE html PUBLIC "foo" "http://www.w3.org/TR/html4/foo.dtd">',
                    'messageId' => 'document-type-invalid',
                    'type' => 'error',
                ],
            ],
        ];
    }

    /**
     * @dataProvider performSuccessDataProvider
     *
     * @param string $content
     * @param string $htmlValidatorOutput
     * @param bool $expectedHasSucceeded
     * @param bool $expectedIsRetryable
     * @param int $expectedErrorCount
     * @param array $expectedDecodedOutput
     */
    public function testPerformSuccess(
        $content,
        $htmlValidatorOutput,
        $expectedHasSucceeded,
        $expectedIsRetryable,
        $expectedErrorCount,
        $expectedDecodedOutput
    ) {
        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], $content),
        ]);

        $task = $this->testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults()
        );

        HtmlValidatorFixtureFactory::set($htmlValidatorOutput);

        $taskDriverResponse = $this->taskDriver->perform($task);

        $this->assertEquals($expectedHasSucceeded, $taskDriverResponse->hasSucceeded());
        $this->assertEquals($expectedIsRetryable, $taskDriverResponse->isRetryable());
        $this->assertEquals($expectedErrorCount, $taskDriverResponse->getErrorCount());

        $this->assertEquals(
            $expectedDecodedOutput,
            json_decode($taskDriverResponse->getTaskOutput()->getOutput(), true)
        );
    }

    /**
     * @return array
     */
    public function performSuccessDataProvider()
    {
        return [
            'no errors' => [
                'content' => '<!DOCTYPE html>',
                'htmlValidatorOutput' => HtmlValidatorFixtureFactory::load('0-errors'),
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 0,
                'expectedDecodedOutput' => [
                    'messages' => [],
                ],
            ],
            'one error' => [
                'content' => '<!DOCTYPE html>',
                'htmlValidatorOutput' => HtmlValidatorFixtureFactory::load('1-error'),
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 1,
                'expectedDecodedOutput' => [
                    'messages' => [
                        [
                            'lastLine' => 188,
                            'lastColumn' => 79,
                            'message' => 'An img element must have an alt attribute, except under certain conditions.',
                            'messageid' => 'html5',
                            'explanation' => 'explanatory text',
                            'type' => 'error',
                        ]
                    ],
                ],
            ],
            'three errors' => [
                'content' => '<!DOCTYPE html>',
                'htmlValidatorOutput' => HtmlValidatorFixtureFactory::load('3-errors'),
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 3,
                'expectedDecodedOutput' => [
                    'messages' => [
                        [
                            'lastLine' => 188,
                            'lastColumn' => 79,
                            'message' => 'An img element must have an alt attribute, except under certain conditions.',
                            'messageid' => 'html5',
                            'explanation' => 'explanatory text',
                            'type' => 'error',
                        ],
                        [
                            'lastLine' => 188,
                            'lastColumn' => 79,
                            'message' => 'An img element must have an alt attribute, except under certain conditions.',
                            'messageid' => 'html5',
                            'explanation' => 'explanatory text',
                            'type' => 'error',
                        ],
                        [
                            'lastLine' => 188,
                            'lastColumn' => 79,
                            'message' => 'An img element must have an alt attribute, except under certain conditions.',
                            'messageid' => 'html5',
                            'explanation' => 'explanatory text',
                            'type' => 'error',
                        ],
                    ],
                ],
            ],
            'internal software error' => [
                'content' => '<!DOCTYPE html>',
                'htmlValidatorOutput' => HtmlValidatorFixtureFactory::load('internal-software-error'),
                'expectedHasSucceeded' => false,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 0,
                'expectedDecodedOutput' => [
                    'messages' => [
                        [
                            'message' => 'Sorry, this document can\'t be checked',
                            'messageId' => 'validator-internal-server-error',
                            'type' => 'error',
                        ]
                    ],
                ],
            ],
            'invalid character encoding' => [
                'content' => '<!DOCTYPE html>',
                'htmlValidatorOutput' => HtmlValidatorFixtureFactory::load('invalid-character-encoding-error'),
                'expectedHasSucceeded' => false,
                'expectedIsRetryable' => false,
                'expectedErrorCount' => 1,
                'expectedDecodedOutput' => [
                    'messages' => [
                        [
                            'message' => '<p>
        Sorry, I am unable to validate this document because on line
        <strong>101</strong>
        it contained one or more bytes that I cannot interpret as
        <code>utf-8</code>
        (in other words, the bytes found are not valid values in the specified
        Character Encoding). Please check both the content of the file and the
        character encoding indication.
      </p><p>The error was:
        utf8 "\xE1" does not map to Unicode

      </p>',
                            'messageId' => 'character-encoding',
                            'type' => 'error',
                        ]
                    ],
                ],
            ],
            'css validation errors only, ignored' => [
                'content' => '<!DOCTYPE html>',
                'htmlValidatorOutput' => HtmlValidatorFixtureFactory::load('css-errors-only'),
                'expectedHasSucceeded' => true,
                'expectedIsRetryable' => true,
                'expectedErrorCount' => 0,
                'expectedDecodedOutput' => [
                    'messages' => [],
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
            new Response(200, ['content-type' => 'text/html'], '<!doctype html>'),
        ]);

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters)
        ]));

        $this->taskDriver->perform($task);

        /* @var array $historicalRequests */
        $historicalRequests = $this->httpHistoryContainer->getRequests();
        $this->assertCount(2, $historicalRequests);

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
            new Response(200, ['content-type' => 'text/html'], '<!doctype html>'),
        ]);

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters),
        ]));

        $this->taskDriver->perform($task);

        /* @var array $historicalRequests */
        $historicalRequests = $this->httpHistoryContainer->getRequests();
        $this->assertCount(2, $historicalRequests);

        foreach ($historicalRequests as $historicalRequest) {
            $authorizationHeaderLine = $historicalRequest->getHeaderLine('authorization');

            $decodedAuthorizationHeaderValue = base64_decode(
                str_replace('Basic ', '', $authorizationHeaderLine)
            );

            $this->assertEquals($expectedRequestAuthorizationHeaderValue, $decodedAuthorizationHeaderValue);
        }
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

        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], $content),
        ]);

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults()
        );

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

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
