<?php

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
     * @var TaskPreparer
     */
    private $taskPreparer;

    /**
     * @var TestTaskFactory
     */
    private $testTaskFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskPreparer = self::$container->get(TaskPreparer::class);
        $this->testTaskFactory = self::$container->get(TestTaskFactory::class);
    }

    /**
     * @dataProvider prepareNoTaskTypePreparerDataProvider
     *
     * @param array $taskValues
     */
    public function testPrepareNoTaskTypePreparer($taskValues)
    {
        $task = $this->testTaskFactory->create($taskValues);

        $eventDispatcher = \Mockery::mock(EventDispatcherInterface::class);
        $eventDispatcher
            ->shouldReceive('dispatch')
            ->once()
            ->withArgs(function (string $eventName, TaskEvent $taskEvent) use ($task) {
                $this->assertEquals(TaskEvent::TYPE_PREPARED, $eventName);
                $this->assertSame($task, $taskEvent->getTask());

                return true;
            });

        ObjectPropertySetter::setProperty(
            $this->taskPreparer,
            TaskPreparer::class,
            'eventDispatcher',
            $eventDispatcher
        );

        $this->taskPreparer->prepare($task);

        $this->assertEquals(Task::STATE_PREPARED, $task->getState());
    }

    /**
     * @return array
     */
    public function prepareNoTaskTypePreparerDataProvider()
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

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
