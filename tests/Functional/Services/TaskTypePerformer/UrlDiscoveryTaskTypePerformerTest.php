<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\Task\TypeInterface;
use App\Services\TaskTypePerformer\TaskPerformerInterface;
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

    protected function getTaskTypePerformer(): TaskPerformerInterface
    {
        return self::$container->get(UrlDiscoveryTaskTypePerformer::class);
    }

    protected function getTaskTypeString(): string
    {
        return TypeInterface::TYPE_URL_DISCOVERY;
    }

    /**
     * @dataProvider performSuccessDataProvider
     */
    public function testPerformSuccess(
        array $taskParameters,
        string $webPageContent,
        array $expectedDecodedOutput
    ) {
        $task = $this->createTaskWithPrimarySource(
            TestTaskFactory::createTaskValuesFromDefaults([
                'type' => $this->getTaskTypeString(),
                'parameters' => json_encode($taskParameters),
            ]),
            $webPageContent
        );

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

    public function testHandles()
    {
        $this->assertFalse($this->taskTypePerformer->handles(TypeInterface::TYPE_HTML_VALIDATION));
        $this->assertFalse($this->taskTypePerformer->handles(TypeInterface::TYPE_CSS_VALIDATION));
        $this->assertFalse($this->taskTypePerformer->handles(TypeInterface::TYPE_LINK_INTEGRITY));
        $this->assertFalse($this->taskTypePerformer->handles(TypeInterface::TYPE_LINK_INTEGRITY_SINGLE_URL));
        $this->assertTrue($this->taskTypePerformer->handles(TypeInterface::TYPE_URL_DISCOVERY));
    }

    public function testGetPriority()
    {
        $this->assertEquals(
            self::$container->getParameter('url_discovery_task_type_performer_priority'),
            $this->taskTypePerformer->getPriority()
        );
    }
}
