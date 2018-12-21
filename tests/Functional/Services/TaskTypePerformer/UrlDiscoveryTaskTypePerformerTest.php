<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\Task\TypeInterface;
use App\Services\TaskTypePerformer\TaskTypePerformerInterface;
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

    protected function getTaskTypePerformer(): TaskTypePerformerInterface
    {
        return $this->taskTypePerformer;
    }

    protected function getTaskTypeString(): string
    {
        return TypeInterface::TYPE_URL_DISCOVERY;
    }

    /**
     * @dataProvider performSuccessDataProvider
     */
    public function testPerformSuccess(
        string $webPageContent,
        array $taskParameters,
        array $expectedDecodedOutput
    ) {
        $task = $this->testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults([
                'type' => $this->getTaskTypeString(),
                'parameters' => json_encode($taskParameters),
            ])
        );

        $this->setTaskPerformerWebPageRetrieverOnTaskPerformer(
            UrlDiscoveryTaskTypePerformer::class,
            $task,
            $webPageContent
        );

        $this->taskTypePerformer->perform($task);

        $this->assertEquals(Task::STATE_COMPLETED, $task->getState());

        $output = $task->getOutput();
        $this->assertInstanceOf(Output::class, $output);
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
                'webPageContent' => HtmlDocumentFactory::load('minimal'),
                'taskParameters' => [],
                'expectedDecodedOutput' => [],
            ],
            'no scope' => [
                'webPageContent' => HtmlDocumentFactory::load('css-link-js-link-image-anchors'),
                'taskParameters' => [],
                'expectedDecodedOutput' => [
                    'http://example.com/foo/anchor1',
                    'http://www.example.com/foo/anchor2',
                    'http://bar.example.com/bar/anchor',
                    'https://www.example.com/foo/anchor1',
                ],
            ],
            'has scope' => [
                'webPageContent' => HtmlDocumentFactory::load('css-link-js-link-image-anchors'),
                'taskParameters' => [
                    'scope' => [
                        'http://example.com',
                        'http://www.example.com',
                    ]
                ],
                'expectedDecodedOutput' => [
                    'http://example.com/foo/anchor1',
                    'http://www.example.com/foo/anchor2',
                    'https://www.example.com/foo/anchor1',
                ],
            ],
        ];
    }
}
