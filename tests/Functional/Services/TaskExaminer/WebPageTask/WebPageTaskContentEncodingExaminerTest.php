<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services\TaskExaminer\WebPageTask;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\Services\TaskCachedSourceWebPageRetriever;
use App\Services\TaskExaminer\WebPageTask\ContentEncodingExaminer;
use App\Tests\Factory\HtmlDocumentFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\ContentTypeFactory;
use App\Tests\Services\ObjectReflector;
use App\Tests\Services\TestTaskFactory;
use Mockery\MockInterface;
use webignition\WebResource\WebPage\WebPage;

class WebPageTaskContentEncodingExaminerTest extends AbstractBaseTestCase
{
    /**
     * @var ContentEncodingExaminer
     */
    private $examiner;

    /**
     * @var Task
     */
    private $task;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->examiner = self::$container->get(ContentEncodingExaminer::class);

        $testTaskFactory = self::$container->get(TestTaskFactory::class);
        $this->task = $testTaskFactory->create(
            TestTaskFactory::createTaskValuesFromDefaults()
        );
    }

    /**
     * @dataProvider examineNoChangesDataProvider
     */
    public function testInvokeNoChanges(callable $webPageCreator, bool $expectedPropagationIsStopped)
    {
        $webPage = $webPageCreator();

        $taskCachedSourceWebPageRetriever = $this->createTaskCachedSourceWebPageRetriever($this->task, $webPage);
        $this->setExaminerTaskCachedSourceWebPageRetriever($taskCachedSourceWebPageRetriever);

        $taskEvent = new TaskEvent($this->task);

        $this->examiner->__invoke($taskEvent);

        $this->assertEquals($expectedPropagationIsStopped, $taskEvent->isPropagationStopped());
    }

    /**
     * @dataProvider examineNoChangesDataProvider
     */
    public function testExamineNoChanges(callable $webPageCreator, bool $expectedPropagationIsStopped)
    {
        $webPage = $webPageCreator();

        $taskCachedSourceWebPageRetriever = $this->createTaskCachedSourceWebPageRetriever($this->task, $webPage);
        $this->setExaminerTaskCachedSourceWebPageRetriever($taskCachedSourceWebPageRetriever);

        $taskState = $this->task->getState();

        $expectedReturnValue = !$expectedPropagationIsStopped;

        $returnValue = $this->examiner->examine($this->task);
        $this->assertEquals($expectedReturnValue, $returnValue);

        $this->assertEquals($taskState, $this->task->getState());
        $this->assertNull($this->task->getOutput());
    }

    public function examineNoChangesDataProvider()
    {
        return [
            'task has no web page primary source' => [
                'webPageCreator' => function () {
                    return null;
                },
                'expectedPropagationIsStopped' => true,
            ],
            'task as valid web page as primary source' => [
                'webPageCreator' => function (): WebPage {
                    /* @var WebPage $webPage */
                    $webPage = WebPage::createFromContent(
                        'web page content'
                    );

                    return $webPage;
                },
                'expectedPropagationIsStopped' => false,
            ],
        ];
    }

    /**
     * @dataProvider examineSetsTaskAsFailedDataProvider
     */
    public function testInvokeSetsTaskAsFailed(callable $webPageCreator)
    {
        $webPage = $webPageCreator();

        $taskCachedSourceWebPageRetriever = $this->createTaskCachedSourceWebPageRetriever($this->task, $webPage);
        $this->setExaminerTaskCachedSourceWebPageRetriever($taskCachedSourceWebPageRetriever);

        $taskEvent = new TaskEvent($this->task);

        $this->examiner->__invoke($taskEvent);

        $this->assertTrue($taskEvent->isPropagationStopped());
    }

    /**
     * @dataProvider examineSetsTaskAsFailedDataProvider
     */
    public function testExamineSetsTaskAsFailed(callable $webPageCreator, string $expectedCharacterSetInOutput)
    {
        /* @var WebPage $webPage */
        $webPage = $webPageCreator();

        $taskCachedSourceWebPageRetriever = $this->createTaskCachedSourceWebPageRetriever($this->task, $webPage);
        $this->setExaminerTaskCachedSourceWebPageRetriever($taskCachedSourceWebPageRetriever);

        $this->examiner->examine($this->task);

        $this->assertEquals(Task::STATE_FAILED_NO_RETRY_AVAILABLE, $this->task->getState());

        $taskOutput = $this->task->getOutput();
        $this->assertInstanceOf(Output::class, $taskOutput);

        if ($taskOutput instanceof Output) {
            $this->assertEquals(1, $taskOutput->getErrorCount());
            $this->assertEquals(0, $taskOutput->getWarningCount());
            $this->assertEquals(json_encode([
                'messages' => [
                    [
                        'message' => $expectedCharacterSetInOutput,
                        'messageId' => 'invalid-character-encoding',
                        'type' => 'error',
                    ],
                ],
            ]), $taskOutput->getOutput());
        }
    }

    public function examineSetsTaskAsFailedDataProvider()
    {
        return [
            'Invalid two-octet sequence with no specific encoding' => [
                'webPageCreator' => function (): WebPage {
                    /* @var WebPage $webPage */
                    $webPage = WebPage::createFromContent(
                        "\xc3\x28"
                    );

                    return $webPage;
                },
                'expectedCharacterSetInOutput' => 'utf-8',
            ],
            'Invalid two-octet sequence with utf-8 encoding' => [
                'webPageCreator' => function (): WebPage {
                    $contentTypeFactory = self::$container->get(ContentTypeFactory::class);

                    /* @var WebPage $webPage */
                    $webPage = WebPage::createFromContent(
                        "\xc3\x28"
                    );

                    $webPage->setContentType($contentTypeFactory->createContentType('text/html; charset=utf-8'));

                    return $webPage;
                },
                'expectedCharacterSetInOutput' => 'utf-8',
            ],
            'Invalid two-octet sequence with utf-16 encoding' => [
                'webPageCreator' => function (): WebPage {
                    $contentTypeFactory = self::$container->get(ContentTypeFactory::class);

                    /* @var WebPage $webPage */
                    $webPage = WebPage::createFromContent(
                        "\xc3\x28"
                    );

                    $webPage = $webPage->setContentType(
                        $contentTypeFactory->createContentType('text/html; charset=utf-16')
                    );

                    return $webPage;
                },
                'expectedCharacterSetInOutput' => 'utf-16',
            ],
            'document with invalid windows-1251 encoding, no charset in page content type' => [
                'webPageCreator' => function (): WebPage {
                    $contentTypeFactory = self::$container->get(ContentTypeFactory::class);

                    /* @var WebPage $webPage */
                    $webPage = WebPage::createFromContent(
                        HtmlDocumentFactory::load('invalid-windows-1251-encoding')
                    );

                    $webPage = $webPage->setContentType(
                        $contentTypeFactory->createContentType('text/html')
                    );

                    return $webPage;
                },
                'expectedCharacterSetInOutput' => 'windows-1251',
            ],
            'document with invalid windows-1251 encoding, charset in page content type is correctly ignored' => [
                'webPageCreator' => function (): WebPage {
                    $contentTypeFactory = self::$container->get(ContentTypeFactory::class);

                    /* @var WebPage $webPage */
                    $webPage = WebPage::createFromContent(
                        HtmlDocumentFactory::load('invalid-windows-1251-encoding')
                    );

                    $webPage = $webPage->setContentType(
                        $contentTypeFactory->createContentType('text/html; charset=utf-8')
                    );

                    return $webPage;
                },
                'expectedCharacterSetInOutput' => 'windows-1251',
            ],
        ];
    }

    /**
     * @dataProvider examineCompleteTaskDataProvider
     */
    public function testExamineCompleteTask(string $state)
    {
        $this->task->setState($state);

        $this->assertFalse($this->examiner->examine($this->task));
    }

    public function examineCompleteTaskDataProvider(): array
    {
        return [
            'completed' => [
                'state' => Task::STATE_COMPLETED,
            ],
            'cancelled' => [
                'state' => Task::STATE_CANCELLED,
            ],
            'failed no retry available' => [
                'state' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
            ],
            'failed retry available' => [
                'state' => Task::STATE_FAILED_RETRY_AVAILABLE,
            ],
            'failed retry limit reached' => [
                'state' => Task::STATE_FAILED_RETRY_LIMIT_REACHED,
            ],
            'skipped' => [
                'state' => Task::STATE_SKIPPED,
            ],
        ];
    }

    /**
     * @param Task $task
     * @param WebPage|null $webPage
     *
     * @return TaskCachedSourceWebPageRetriever|MockInterface
     */
    private function createTaskCachedSourceWebPageRetriever(Task $task, ?WebPage $webPage = null)
    {
        $taskCachedSourceWebPageRetriever = \Mockery::mock(TaskCachedSourceWebPageRetriever::class);
        $taskCachedSourceWebPageRetriever
            ->shouldReceive('retrieve')
            ->with($task)
            ->andReturn($webPage);

        return $taskCachedSourceWebPageRetriever;
    }

    private function setExaminerTaskCachedSourceWebPageRetriever(
        TaskCachedSourceWebPageRetriever $taskCachedSourceWebPageRetriever
    ) {
        ObjectReflector::setProperty(
            $this->examiner,
            ContentEncodingExaminer::class,
            'taskCachedSourceWebPageRetriever',
            $taskCachedSourceWebPageRetriever
        );
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
