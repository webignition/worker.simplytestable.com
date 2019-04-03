<?php
/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services\TaskTypePerformer;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Exception\UnableToPerformTaskException;
use App\Model\Task\TypeInterface;
use App\Services\TaskCachedSourceWebPageRetriever;
use App\Tests\Services\ObjectReflector;
use App\Tests\Services\TestTaskFactory;
use App\Services\TaskTypePerformer\UrlDiscoveryTaskTypePerformer;
use App\Tests\Factory\HtmlDocumentFactory;
use webignition\InternetMediaType\InternetMediaType;

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

        $output = Output::create('', new InternetMediaType('application', 'json'));
        $task->setOutput($output);
        $this->assertSame($output, $task->getOutput());

        $taskState = $task->getState();

        $this->taskTypePerformer->perform($task);

        $this->assertEquals($taskState, $task->getState());
        $this->assertSame($output, $task->getOutput());
    }

    public function testPerformUnableToRetrieveCachedWebPage()
    {
        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => TypeInterface::TYPE_URL_DISCOVERY,
        ]));
        $this->testTaskFactory->addPrimaryCachedResourceSourceToTask($task, '<!DOCTYPE html>');

        $taskCachedSourceWebPageRetriever = \Mockery::mock(TaskCachedSourceWebPageRetriever::class);
        $taskCachedSourceWebPageRetriever
            ->shouldReceive('retrieve')
            ->with($task)
            ->andReturn(null);

        ObjectReflector::setProperty(
            $this->taskTypePerformer,
            UrlDiscoveryTaskTypePerformer::class,
            'taskCachedSourceWebPageRetriever',
            $taskCachedSourceWebPageRetriever
        );

        $this->expectException(UnableToPerformTaskException::class);

        $this->taskTypePerformer->perform($task);
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

        if ($output instanceof Output) {
            $this->assertEquals('application/json', $output->getContentType());
            $this->assertEquals(0, $output->getErrorCount());
            $this->assertEquals(0, $output->getWarningCount());

            $this->assertEquals(
                $expectedDecodedOutput,
                json_decode((string) $output->getContent(), true)
            );
        }
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
            'has scope' => [
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
            'http and https are equivalent' => [
                'taskParameters' => [],
                'webPageContent' => HtmlDocumentFactory::load('http-and-https-urls'),
                'expectedDecodedOutput' => [
                    'https://example.com/1',
                    'http://example.com/2',
                ],
            ],
        ];
    }
}
