<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services;

use App\Event\TaskEvent;
use App\Model\Task\Type;
use App\Model\Task\TypeInterface;
use App\Services\TaskPreparer;
use App\Tests\Services\ObjectPropertySetter;
use App\Tests\Services\TestTaskFactory;
use App\Entity\Task\Task;
use App\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TaskPreparerTest extends AbstractBaseTestCase
{
    const DEFAULT_TASK_URL = 'http://example.com/';
    const DEFAULT_TASK_PARAMETERS = '';
    const DEFAULT_TASK_TYPE = TypeInterface::TYPE_HTML_VALIDATION;
    const DEFAULT_TASK_STATE = Task::STATE_QUEUED;

    /**
     * @dataProvider prepareDataProvider
     */
    public function testPrepareIsPrepared(array $taskValues)
    {
        $taskPreparer = self::$container->get(TaskPreparer::class);
        $testTaskFactory = self::$container->get(TestTaskFactory::class);

        $task = $testTaskFactory->create($taskValues);

        $eventDispatcher = \Mockery::mock(EventDispatcherInterface::class);

        $dispatchCallCount = 0;
        $expectedEventNames = [
            TaskEvent::TYPE_PREPARE,
            TaskEvent::TYPE_PREPARED,
        ];

        $historicalTaskEvents = [];

        $eventDispatcher
            ->shouldReceive('dispatch')
            ->withArgs(function (
                string $eventName,
                TaskEvent $taskEvent
            ) use (
                &$task,
                &$dispatchCallCount,
                &$historicalTaskEvents,
                $expectedEventNames
            ) {
                $this->assertEquals($expectedEventNames[$dispatchCallCount], $eventName);
                $this->assertSame($task, $taskEvent->getTask());

                if (TaskEvent::TYPE_PREPARE === $eventName) {
                    $task->setState(Task::STATE_PREPARED);
                }

                foreach ($historicalTaskEvents as $historicalTaskEvent) {
                    $this->assertNotSame($historicalTaskEvent, $taskEvent);
                }

                $historicalTaskEvents[] = $taskEvent;

                $dispatchCallCount++;

                return true;
            });

        ObjectPropertySetter::setProperty(
            $taskPreparer,
            TaskPreparer::class,
            'eventDispatcher',
            $eventDispatcher
        );

        $taskPreparer->prepare($task);

        $this->assertEquals(Task::STATE_PREPARED, $task->getState());
    }

    /**
     * @dataProvider prepareDataProvider
     */
    public function testPrepareTaskRemainsPreparing(array $taskValues)
    {
        $taskPreparer = self::$container->get(TaskPreparer::class);
        $testTaskFactory = self::$container->get(TestTaskFactory::class);

        $task = $testTaskFactory->create($taskValues);

        $eventDispatcher = \Mockery::mock(EventDispatcherInterface::class);

        $dispatchCallCount = 0;
        $expectedEventNames = [
            TaskEvent::TYPE_PREPARE,
            TaskEvent::TYPE_CREATED,
        ];

        $historicalTaskEvents = [];

        $eventDispatcher
            ->shouldReceive('dispatch')
            ->withArgs(function (
                string $eventName,
                TaskEvent $taskEvent
            ) use (
                &$task,
                &$dispatchCallCount,
                &$historicalTaskEvents,
                $expectedEventNames
            ) {
                $this->assertEquals($expectedEventNames[$dispatchCallCount], $eventName);
                $this->assertSame($task, $taskEvent->getTask());

                foreach ($historicalTaskEvents as $historicalTaskEvent) {
                    $this->assertNotSame($historicalTaskEvent, $taskEvent);
                }

                $historicalTaskEvents[] = $taskEvent;

                $dispatchCallCount++;

                return true;
            });

        ObjectPropertySetter::setProperty(
            $taskPreparer,
            TaskPreparer::class,
            'eventDispatcher',
            $eventDispatcher
        );

        $taskPreparer->prepare($task);

        $this->assertEquals(Task::STATE_PREPARING, $task->getState());
    }

    public function prepareDataProvider(): array
    {
        return [
            'html validation' => [
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    TestTaskFactory::DEFAULT_TASK_TYPE => Type::TYPE_HTML_VALIDATION,
                ]),
            ],
            'css validation' => [
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    TestTaskFactory::DEFAULT_TASK_TYPE => Type::TYPE_CSS_VALIDATION,
                ]),
            ],
            'link integrity' => [
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    TestTaskFactory::DEFAULT_TASK_TYPE => Type::TYPE_LINK_INTEGRITY,
                ]),
            ],
            'url discovery' => [
                'taskValues' => TestTaskFactory::createTaskValuesFromDefaults([
                    TestTaskFactory::DEFAULT_TASK_TYPE => Type::TYPE_URL_DISCOVERY,
                ]),
            ],
        ];
    }

    public function testPrepareTaskCompletes()
    {
        $taskValues = TestTaskFactory::createTaskValuesFromDefaults([
            TestTaskFactory::DEFAULT_TASK_TYPE => Type::TYPE_HTML_VALIDATION,
        ]);

        $taskPreparer = self::$container->get(TaskPreparer::class);
        $testTaskFactory = self::$container->get(TestTaskFactory::class);

        $task = $testTaskFactory->create($taskValues);

        $this->assertNull($task->getStartDateTime());
        $this->assertNull($task->getEndDateTime());

        $eventDispatcher = \Mockery::mock(EventDispatcherInterface::class);

        $dispatchCallCount = 0;
        $expectedEventNames = [
            TaskEvent::TYPE_PREPARE,
            TaskEvent::TYPE_PERFORMED,
        ];

        $historicalTaskEvents = [];

        $eventDispatcher
            ->shouldReceive('dispatch')
            ->withArgs(function (
                string $eventName,
                TaskEvent $taskEvent
            ) use (
                &$task,
                &$dispatchCallCount,
                &$historicalTaskEvents,
                $expectedEventNames
            ) {
                $this->assertEquals($expectedEventNames[$dispatchCallCount], $eventName);
                $this->assertSame($task, $taskEvent->getTask());

                if (TaskEvent::TYPE_PREPARE === $eventName) {
                    $task->setState(Task::STATE_SKIPPED);
                }

                foreach ($historicalTaskEvents as $historicalTaskEvent) {
                    $this->assertNotSame($historicalTaskEvent, $taskEvent);
                }

                $historicalTaskEvents[] = $taskEvent;

                $dispatchCallCount++;

                return true;
            });

        ObjectPropertySetter::setProperty(
            $taskPreparer,
            TaskPreparer::class,
            'eventDispatcher',
            $eventDispatcher
        );

        $taskPreparer->prepare($task);

        $this->assertNotNull($task->getStartDateTime());
        $this->assertNotNull($task->getEndDateTime());
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
