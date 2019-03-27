<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Unit\Services\TaskTypePerformer\HtmlValidation;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Services\TaskOutputMessageFactory;
use App\Services\TaskTypePerformer\HtmlValidation\InvalidCharacterEncodingOutputTransformer;
use webignition\HttpHistoryContainer\Container as HttpHistoryContainer;
use webignition\InternetMediaType\InternetMediaType;

class HtmlValidationInvalidCharacterEncodingOutputTransformerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var InvalidCharacterEncodingOutputTransformer
     */
    private $transformer;

    protected function setUp()
    {
        parent::setUp();

        /* @var HttpHistoryContainer $httpHistoryContainer */
        $httpHistoryContainer = \Mockery::mock(HttpHistoryContainer::class);

        $taskOutputMessageFactory = new TaskOutputMessageFactory($httpHistoryContainer);

        $this->transformer = new InvalidCharacterEncodingOutputTransformer($taskOutputMessageFactory);
    }

    public function testPerformNoOutput()
    {
        $task = new Task();
        $this->assertEmpty($task->getOutput());

        $this->transformer->perform($task);
        $this->assertEmpty($task->getOutput());
    }

    public function testPerformNoMessages()
    {
        $decodedOutput = [];

        $output = Output::create((string) json_encode($decodedOutput), new InternetMediaType('application', 'json'));

        $task = new Task();
        $task->setOutput($output);
        $this->assertEquals($decodedOutput, json_decode((string) $output->getOutput(), true));

        $this->transformer->perform($task);
        $this->assertEquals($decodedOutput, json_decode((string) $output->getOutput(), true));
    }

    /**
     * @dataProvider performHasMessagesDataProvider
     */
    public function testPerformHasMessages(array $outputContent, array $expectedDecodedOutput)
    {
        $output = Output::create((string) json_encode($outputContent), new InternetMediaType('application', 'json'));

        $task = new Task();
        $task->setOutput($output);
        $this->assertEquals($outputContent, json_decode((string) $output->getOutput(), true));

        $this->transformer->perform($task);
        $output = $task->getOutput();
        if ($output instanceof Output) {
            $this->assertEquals($expectedDecodedOutput, json_decode((string) $output->getOutput(), true));
        }
    }

    public function performHasMessagesDataProvider()
    {
        return [
            'more than one message' => [
                'outputContent' => [
                    'messages' => [
                        [
                            'message' => 'foo',
                        ],
                        [
                            'message' => 'bar',
                        ],
                    ]
                ],
                'expectedDecodedOutput' => [
                    'messages' => [
                        [
                            'message' => 'foo',
                        ],
                        [
                            'message' => 'bar',
                        ],
                    ]
                ],
            ],
            'one non-relevant message' => [
                'outputContent' => [
                    'messages' => [
                        [
                            'message' => 'foo',
                        ],
                    ]
                ],
                'expectedDecodedOutput' => [
                    'messages' => [
                        [
                            'message' => 'foo',
                        ],
                    ]
                ],
            ],
            'one relevant message' => [
                'outputContent' => [
                    'messages' => [
                        [
                            'message' => 'foo <code>utf-8</code> bar',
                            'messageId' => 'character-encoding',
                        ],
                    ]
                ],
                'expectedDecodedOutput' => [
                    'messages' => [
                        [
                            'message' => 'utf-8',
                            'messageId' => 'invalid-character-encoding',
                            'type' => 'error',
                        ],
                    ]
                ],
            ],
        ];
    }
}
