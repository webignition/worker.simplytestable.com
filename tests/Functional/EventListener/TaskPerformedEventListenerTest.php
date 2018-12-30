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
    public function testInvokeForSingleUseCachedResource()
    {
        $testTaskFactory = self::$container->get(TestTaskFactory::class);
        $entityManager = self::$container->get(EntityManagerInterface::class);

        $task = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => TypeInterface::TYPE_HTML_VALIDATION,
        ]));
        $task->setState(Task::STATE_IN_PROGRESS);

        $taskId = $task->getId();

        $testTaskFactory->addPrimaryCachedResourceSourceToTask($task, 'content');

        $sources = $task->getSources();
        $primarySource = $sources[$task->getUrl()];
        $primarySourceRequestHash = $primarySource->getValue();

        $this->assertNotNull($entityManager->find(CachedResource::class, $primarySourceRequestHash));

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
            ->withArgs(function (TaskReportCompletionJob $taskReportCompletionJob) use ($taskId) {
                $this->assertEquals(['id' => $taskId], $taskReportCompletionJob->args);

                return true;
            });

        $this->assertNull($entityManager->find(CachedResource::class, $primarySourceRequestHash));
    }

    public function testInvokeForMultipleUseCachedResource()
    {
        $testTaskFactory = self::$container->get(TestTaskFactory::class);
        $entityManager = self::$container->get(EntityManagerInterface::class);

        $htmlValidationTask = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => TypeInterface::TYPE_HTML_VALIDATION,
        ]));
        $htmlValidationTask->setState(Task::STATE_IN_PROGRESS);
        $htmlValidationTaskId = $htmlValidationTask->getId();

        $testTaskFactory->addPrimaryCachedResourceSourceToTask($htmlValidationTask, 'content');

        $sources = $htmlValidationTask->getSources();
        $primarySource = $sources[$htmlValidationTask->getUrl()];
        $primarySourceRequestHash = $primarySource->getValue();

        $this->assertNotNull($entityManager->find(CachedResource::class, $primarySourceRequestHash));

        $cssValidationTask = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => TypeInterface::TYPE_HTML_VALIDATION,
        ]));
        $cssValidationTask->setState(Task::STATE_IN_PROGRESS);

        $cssValidationTask->addSource($primarySource);

        $entityManager->persist($cssValidationTask);
        $entityManager->flush();

        $taskEvent = new TaskEvent($htmlValidationTask);

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
            ->withArgs(function (TaskReportCompletionJob $taskReportCompletionJob) use ($htmlValidationTaskId) {
                $this->assertEquals(['id' => $htmlValidationTaskId], $taskReportCompletionJob->args);

                return true;
            });

        $this->assertNotNull($entityManager->find(CachedResource::class, $primarySourceRequestHash));
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
