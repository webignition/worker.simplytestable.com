<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\Task\TypeInterface;
use App\Tests\Services\TestTaskFactory;
use App\Services\TaskTypePerformer\UrlDiscoveryTaskTypePerformer;
use App\Tests\Factory\HtmlDocumentFactory;

class UrlDiscoveryTaskTypePerformerTest extends AbstractWebPageTaskTypePerformerTest
{
    /**
     * @var UrlDiscoveryTaskTypePerformer
     */
    private $taskTypePerformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->taskTypePerformer = self::$container->get(UrlDiscoveryTaskTypePerformer::class);
    }

    public function testPerformAlreadyHasOutput()
    {
        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => TypeInterface::TYPE_URL_DISCOVERY,
        ]));

        $output = Output::create();
        $task->setOutput($output);
        $this->assertSame($output, $task->getOutput());

        $taskState = $task->getState();

        $this->taskTypePerformer->perform($task);

        $this->assertEquals($taskState, $task->getState());
        $this->assertSame($output, $task->getOutput());
    }

    /**
     * @dataProvider performSuccessDataProvider
     */
    public function testPerformSuccess(
        array $taskParameters,
        string $webPageContent,
        array $expectedDecodedOutput
    ) {
        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => TypeInterface::TYPE_URL_DISCOVERY,
            'parameters' => json_encode($taskParameters),
        ]));

        $this->testTaskFactory->addPrimaryCachedResourceSourceToTask($task, $webPageContent);

        $this->taskTypePerformer->perform($task);

        $this->assertEquals(Task::STATE_COMPLETED, $task->getState());

        $output = $task->getOutput();
        $this->assertInstanceOf(Output::class, $output);
        $this->assertEquals('application/json', $output->getContentType());
        $this->assertEquals(0, $output->getErrorCount());
        $this->assertEquals(0, $output->getWarningCount());

        $this->assertEquals(
            $expectedDecodedOutput,
            json_decode($output->getOutput(), true)
        );
    }

    public function performSuccessDataProvider(): array
    {
        return [
            'no urls' => [
                'taskParameters' => [],
                'webPageContent' => HtmlDocumentFactory::load('minimal'),
                'expectedDecodedOutput' => [],
            ],
            'no scope' => [
                'taskParameters' => [],
                'webPageContent' => HtmlDocumentFactory::load('css-link-js-link-image-anchors'),
                'expectedDecodedOutput' => [
                    'http://example.com/foo/anchor1',
                    'http://www.example.com/foo/anchor2',
                    'http://bar.example.com/bar/anchor',
                    'https://www.example.com/foo/anchor1',
                ],
            ],
            'has scope, no sources' => [
                'taskParameters' => [
                    'scope' => [
                        'http://example.com',
                        'http://www.example.com',
                    ],
                ],
                'webPageContent' => HtmlDocumentFactory::load('css-link-js-link-image-anchors'),
                'expectedDecodedOutput' => [
                    'http://example.com/foo/anchor1',
                    'http://www.example.com/foo/anchor2',
                    'https://www.example.com/foo/anchor1',
                ],
            ],
        ];
    }
}
