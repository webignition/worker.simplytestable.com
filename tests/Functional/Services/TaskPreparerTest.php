<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services;

use App\Event\TaskEvent;
use App\Model\Task\Type;
use App\Model\Task\TypeInterface;
use App\Model\TaskPreparerCollection;
use App\Services\TaskPreparer;
use App\Services\TaskTypePreparer\Factory;
use App\Services\TaskTypePreparer\TaskPreparerInterface;
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
    public function testPrepare(array $taskValues)
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

        $eventDispatcher
            ->shouldReceive('dispatch')
            ->withArgs(function (
                string $eventName,
                TaskEvent $taskEvent
            ) use (
                $task,
                &$dispatchCallCount,
                $expectedEventNames
            ) {
                $this->assertEquals($expectedEventNames[$dispatchCallCount], $eventName);
                $this->assertSame($task, $taskEvent->getTask());

                $dispatchCallCount++;

                return true;
            });

        $taskTypePreparer = \Mockery::mock(TaskPreparerInterface::class);
        $taskTypePreparer
            ->shouldReceive('getPriority')
            ->once()
            ->andReturn(0);

        $taskTypePreparer
            ->shouldReceive('prepare')
            ->once()
            ->with($task);

        $taskPreparerCollection = new TaskPreparerCollection([
            $taskTypePreparer,
        ]);

        $taskTypePreparerFactory = \Mockery::mock(Factory::class);
        $taskTypePreparerFactory
            ->shouldReceive('getPreparers')
            ->with((string) $task->getType())
            ->andReturn($taskPreparerCollection);

        ObjectPropertySetter::setProperty(
            $taskPreparer,
            TaskPreparer::class,
            'eventDispatcher',
            $eventDispatcher
        );

        ObjectPropertySetter::setProperty(
            $taskPreparer,
            TaskPreparer::class,
            'taskTypePreparerFactory',
            $taskTypePreparerFactory
        );

        $taskPreparer->prepare($task);

        $this->assertEquals(Task::STATE_PREPARED, $task->getState());
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

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
