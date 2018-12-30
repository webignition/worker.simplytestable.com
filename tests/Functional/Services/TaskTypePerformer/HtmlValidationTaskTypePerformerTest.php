<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\Task\TypeInterface;
use App\Tests\Services\TestTaskFactory;
use App\Services\TaskTypePerformer\HtmlValidationTaskTypePerformer;
use App\Tests\Factory\HtmlValidatorFixtureFactory;

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

    /**
     * @dataProvider badDocumentTypeDataProvider
     */
    public function testPerformBadDocumentType(string $content, array $expectedOutputMessage)
    {
        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults());
        $this->testTaskFactory->addPrimaryCachedResourceSourceToTask($task, $content);

        $this->taskTypePerformer->perform($task);

        $this->assertEquals(Task::STATE_FAILED_NO_RETRY_AVAILABLE, $task->getState());

        $output = $task->getOutput();
        $this->assertInstanceOf(Output::class, $output);
        $this->assertEquals('application/json', $output->getContentType());
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
        string $htmlValidatorOutput,
        string $expectedTaskState,
        int $expectedErrorCount,
        array $expectedDecodedOutput
    ) {
        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults());
        $this->testTaskFactory->addPrimaryCachedResourceSourceToTask($task, '<!DOCTYPE html>');

        HtmlValidatorFixtureFactory::set($htmlValidatorOutput);

        $this->taskTypePerformer->perform($task);

        $this->assertEquals($expectedTaskState, $task->getState());

        $output = $task->getOutput();

        $this->assertInstanceOf(Output::class, $output);
        $this->assertEquals('application/json', $output->getContentType());
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
                'htmlValidatorOutput' => HtmlValidatorFixtureFactory::load('0-errors'),
                'expectedTaskState' => Task::STATE_COMPLETED,
                'expectedErrorCount' => 0,
                'expectedDecodedOutput' => [
                    'messages' => [],
                ],
            ],
            'one error' => [
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
     * @dataProvider storeTmpFileDataProvider
     */
    public function testStoreTmpFile(bool $fileExists)
    {
        $tmpFilePath = sys_get_temp_dir() . '/f45451f4d07ca1f5bab9ed278e880c5f.html';
        $content = '<!doctype html>';

        if (!$fileExists) {
            @unlink($tmpFilePath);
        } else {
            file_put_contents($tmpFilePath, $content);
        }

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults());
        $this->testTaskFactory->addPrimaryCachedResourceSourceToTask($task, $content);

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

    public function testHandles()
    {
        $this->assertTrue($this->taskTypePerformer->handles(TypeInterface::TYPE_HTML_VALIDATION));
        $this->assertFalse($this->taskTypePerformer->handles(TypeInterface::TYPE_CSS_VALIDATION));
        $this->assertFalse($this->taskTypePerformer->handles(TypeInterface::TYPE_LINK_INTEGRITY));
        $this->assertFalse($this->taskTypePerformer->handles(TypeInterface::TYPE_LINK_INTEGRITY_SINGLE_URL));
        $this->assertFalse($this->taskTypePerformer->handles(TypeInterface::TYPE_URL_DISCOVERY));
    }

    public function testGetPriority()
    {
        $this->assertEquals(
            self::$container->getParameter('html_validation_task_type_performer_priority'),
            $this->taskTypePerformer->getPriority()
        );
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
