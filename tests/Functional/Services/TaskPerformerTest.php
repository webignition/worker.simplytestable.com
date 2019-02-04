<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services;

use App\Event\TaskEvent;
use App\Model\Source;
use App\Model\Task\TypeInterface;
use App\Services\SourceFactory;
use App\Services\TaskPerformer;
use App\Tests\Services\ObjectPropertySetter;
use App\Tests\Services\TestTaskFactory;
use App\Entity\Task\Task;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Factory\HtmlValidatorFixtureFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TaskPerformerTest extends AbstractBaseTestCase
{
    const DEFAULT_TASK_URL = 'http://example.com/';
    const DEFAULT_TASK_PARAMETERS = '';
    const DEFAULT_TASK_TYPE = TypeInterface::TYPE_HTML_VALIDATION;
    const DEFAULT_TASK_STATE = Task::STATE_QUEUED;

    /**
     * @var TaskPerformer
     */
    private $taskPerformer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskPerformer = self::$container->get(TaskPerformer::class);
    }

    /**
     * @dataProvider performDataProvider
     */
    public function testPerform(
        callable $taskCreator,
        callable $setUp,
        array $expectedEventNames,
        string $expectedFinishedStateName
    ) {
        /* @var Task $task */
        $task = $taskCreator();
        $setUp();

        $eventDispatcher = \Mockery::mock(EventDispatcherInterface::class);

        $dispatchCallCount = 0;

        $eventDispatcher
            ->shouldReceive('dispatch')
            ->withArgs(function (
                string $eventName,
                TaskEvent $taskEvent
            ) use (
                &$task,
                &$dispatchCallCount,
                $expectedEventNames,
                $expectedFinishedStateName
            ) {
                $this->assertEquals($expectedEventNames[$dispatchCallCount], $eventName);
                $this->assertSame($task, $taskEvent->getTask());

                if (TaskEvent::TYPE_PERFORM === $eventName) {
                    $task->setState($expectedFinishedStateName);
                }

                $dispatchCallCount++;

                return true;
            });

        ObjectPropertySetter::setProperty(
            $this->taskPerformer,
            TaskPerformer::class,
            'eventDispatcher',
            $eventDispatcher
        );

        $this->taskPerformer->perform($task);

        $this->assertEquals($expectedFinishedStateName, $task->getState());
    }

    public function performDataProvider(): array
    {
        return [
            'not fully performed' => [
                'task' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);

                    $task = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults());
                    $testTaskFactory->addPrimaryCachedResourceSourceToTask(
                        $task,
                        '<!doctype html><html><head></head><body></body>'
                    );

                    return $task;
                },
                'setUp' => function () {
                    HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));
                },
                'expectedEventNames' => [
                    TaskEvent::TYPE_PERFORM,
                    TaskEvent::TYPE_PREPARED,
                ],
                'expectedFinishedStateName' => Task::STATE_IN_PROGRESS,
            ],
            'finished performing; complete' => [
                'task' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);

                    $task = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults());
                    $testTaskFactory->addPrimaryCachedResourceSourceToTask(
                        $task,
                        '<!doctype html><html><head></head><body></body>'
                    );

                    return $task;
                },
                'setUp' => function () {
                    HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));
                },
                'expectedEventNames' => [
                    TaskEvent::TYPE_PERFORM,
                    TaskEvent::TYPE_PERFORMED,
                ],
                'expectedFinishedStateName' => Task::STATE_COMPLETED,
            ],
            'finished performing; skipped' => [
                'task' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);
                    $sourceFactory = self::$container->get(SourceFactory::class);

                    $task = $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults([])
                    );

                    $source = $sourceFactory->createInvalidSource(
                        $task->getUrl(),
                        Source::MESSAGE_INVALID_CONTENT_TYPE
                    );

                    $task->addSource($source);

                    return $task;
                },
                'setUp' => function () {
                },
                'expectedEventNames' => [
                    TaskEvent::TYPE_PERFORM,
                    TaskEvent::TYPE_PERFORMED,
                ],
                'expectedFinishedStateName' => Task::STATE_SKIPPED,
            ],
            'finished performing; failed no retry available' => [
                'task' => function (): Task {
                    $testTaskFactory = self::$container->get(TestTaskFactory::class);
                    $sourceFactory = self::$container->get(SourceFactory::class);

                    $task = $testTaskFactory->create(
                        TestTaskFactory::createTaskValuesFromDefaults([])
                    );

                    $source = $sourceFactory->createHttpFailedSource(
                        $task->getUrl(),
                        404
                    );

                    $task->addSource($source);

                    return $task;
                },
                'setUp' => function () {
                },
                'expectedEventNames' => [
                    TaskEvent::TYPE_PERFORM,
                    TaskEvent::TYPE_PERFORMED,
                ],
                'expectedFinishedStateName' => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
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
