<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\Task\TypeInterface;
use App\Services\TaskPerformerWebPageRetriever;
use App\Services\TaskTypePerformer\TaskTypePerformerInterface;
use App\Tests\Services\ObjectPropertySetter;
use App\Tests\Services\TestTaskFactory;
use GuzzleHttp\Psr7\Response;
use App\Services\TaskTypePerformer\HtmlValidationTaskTypePerformer;
use App\Tests\Factory\HtmlValidatorFixtureFactory;
use webignition\WebResource\WebPage\WebPage;

class HtmlValidationTaskTypePerformerTest extends AbstractWebPageTaskTypePerformerTest
{
    /**
     * @var HtmlValidationTaskTypePerformer
     */
    private $taskTypePerformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->taskTypePerformer = self::$container->get(HtmlValidationTaskTypePerformer::class);
    }

    protected function getTaskTypePerformer(): TaskTypePerformerInterface
    {
        return $this->taskTypePerformer;
    }

    protected function getTaskTypeString(): string
    {
        return TypeInterface::TYPE_HTML_VALIDATION;
    }

    /**
     * @dataProvider badDocumentTypeDataProvider
     */
    public function testPerformBadDocumentType(string $content, array $expectedOutputMessage)
    {
        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], $content),
        ]);

        $task = $this->testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults()
        );

        $this->taskTypePerformer->perform($task);

        $this->assertEquals(Task::STATE_FAILED_NO_RETRY_AVAILABLE, $task->getState());

        $output = $task->getOutput();
        $this->assertInstanceOf(Output::class, $output);
        $this->assertEquals(1, $output->getErrorCount());

        $outputContent = json_decode($output->getOutput(), true);
        $outputMessage = $outputContent['messages'][0];

        $this->assertEquals($expectedOutputMessage, $outputMessage);
    }

    public function badDocumentTypeDataProvider(): array
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
     */
    public function testPerformSuccess(
        string $content,
        string $htmlValidatorOutput,
        string $expectedTaskState,
        int $expectedErrorCount,
        array $expectedDecodedOutput
    ) {
        $task = $this->testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults()
        );

        $this->setTaskPerformerWebPageRetrieverOnTaskPerformer($task, $content);

        HtmlValidatorFixtureFactory::set($htmlValidatorOutput);

        $this->taskTypePerformer->perform($task);

        $this->assertEquals($expectedTaskState, $task->getState());

        $output = $task->getOutput();
        $this->assertInstanceOf(Output::class, $output);
        $this->assertEquals($expectedErrorCount, $output->getErrorCount());

        $this->assertEquals(
            $expectedDecodedOutput,
            json_decode($output->getOutput(), true)
        );
    }

    public function performSuccessDataProvider(): array
    {
        return [
            'no errors' => [
                'content' => '<!DOCTYPE html>',
                'htmlValidatorOutput' => HtmlValidatorFixtureFactory::load('0-errors'),
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 0,
                'expectedDecodedOutput' => [
                    'messages' => [],
                ],
            ],
            'one error' => [
                'content' => '<!DOCTYPE html>',
                'htmlValidatorOutput' => HtmlValidatorFixtureFactory::load('1-error'),
                'expectedTaskState' => Task::STATE_COMPLETED,
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
                'expectedTaskState' => Task::STATE_COMPLETED,
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
                'expectedTaskState' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
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
                'expectedTaskState' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
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
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 0,
                'expectedDecodedOutput' => [
                    'messages' => [],
                ],
            ],
        ];
    }

    /**
     * @dataProvider cookiesDataProvider
     */
    public function testSetCookiesOnRequests(array $taskParameters, string $expectedRequestCookieHeader)
    {
        $httpFixtures = [
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], '<!doctype html>'),
        ];

        $this->httpMockHandler->appendFixtures($httpFixtures);

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters)
        ]));

        $this->taskTypePerformer->perform($task);

        $this->assertCookieHeadeSetOnAllRequests(count($httpFixtures), $expectedRequestCookieHeader);
    }

    /**
     * @dataProvider httpAuthDataProvider
     */
    public function testSetHttpAuthenticationOnRequests(
        array $taskParameters,
        string $expectedRequestAuthorizationHeaderValue
    ) {
        $httpFixtures = [
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], '<!doctype html>'),
        ];

        $this->httpMockHandler->appendFixtures($httpFixtures);

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => $this->getTaskTypeString(),
            'parameters' => json_encode($taskParameters),
        ]));

        $this->taskTypePerformer->perform($task);

        $this->assertHttpAuthorizationSetOnAllRequests(count($httpFixtures), $expectedRequestAuthorizationHeaderValue);
    }

    /**
     * @dataProvider storeTmpFileDataProvider
     */
    public function testStoreTmpFile(bool $fileExists)
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

        $this->taskTypePerformer->perform($task);
    }

    public function storeTmpFileDataProvider(): array
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

    private function setTaskPerformerWebPageRetrieverOnTaskPerformer(Task $task, string $content)
    {
        $webPage = WebPage::createFromContent($content);

        $taskPerformerWebPageRetriever = \Mockery::mock(TaskPerformerWebPageRetriever::class);
        $taskPerformerWebPageRetriever
            ->shouldReceive('retrieveWebPage')
            ->with($task)
            ->andReturn($webPage);

        ObjectPropertySetter::setProperty(
            $this->taskTypePerformer,
            HtmlValidationTaskTypePerformer::class,
            'taskPerformerWebPageRetriever',
            $taskPerformerWebPageRetriever
        );
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
