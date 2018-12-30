<?php
/** @noinspection PhpUnhandledExceptionInspection */

namespace App\Tests\Functional\EventListener;

use App\Entity\CachedResource;
use App\Entity\Task\Task;
use App\Event\TaskEvent;
use App\EventListener\TaskPerformedEventListener;
use App\Model\Task\TypeInterface;
use App\Resque\Job\TaskReportCompletionJob;
use App\Tests\Services\ObjectPropertySetter;
use App\Services\Resque\QueueService;
use App\Tests\Services\TestTaskFactory;
use Doctrine\ORM\EntityManagerInterface;

class TaskPerformedEventListenerTest extends AbstractTaskEventListenerTest
{
    public function testInvoke()
    {
        $testTaskFactory = self::$container->get(TestTaskFactory::class);
        $entityManager = self::$container->get(EntityManagerInterface::class);

        $task = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => TypeInterface::TYPE_HTML_VALIDATION,
        ]));

        $testTaskFactory->addPrimaryCachedResourceSourceToTask($task, 'content');

        $sources = $task->getSources();
        $primarySource = $sources[$task->getUrl()];
        $primarySourceRequestHash = $primarySource->getValue();

        $this->assertNotNull($entityManager->find(CachedResource::class, $primarySourceRequestHash));

        ObjectPropertySetter::setProperty($task, Task::class, 'id', self::TASK_ID);
        $taskEvent = new TaskEvent($task);

        $taskPerformedEventListener = self::$container->get(TaskPerformedEventListener::class);

        $resqueQueueService = \Mockery::spy(QueueService::class);

        ObjectPropertySetter::setProperty(
            $taskPerformedEventListener,
            TaskPerformedEventListener::class,
            'resqueQueueService',
            $resqueQueueService
        );

        $this->eventDispatcher->dispatch(TaskEvent::TYPE_PERFORMED, $taskEvent);

        $resqueQueueService
            ->shouldHaveReceived('enqueue')
            ->withArgs(function (TaskReportCompletionJob $taskReportCompletionJob) {
                $this->assertEquals(['id' => self::TASK_ID], $taskReportCompletionJob->args);

                return true;
            });

        $this->assertNull($entityManager->find(CachedResource::class, $primarySourceRequestHash));
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
