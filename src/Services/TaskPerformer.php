<?php

namespace App\Services;

use App\Event\TaskEvent;
use App\Services\TaskTypePerformer\TaskTypePerformerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task\Task;
use App\Model\TaskTypePerformer\Response as TaskTypePerformerResponse;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TaskPerformer
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var TaskTypePerformerInterface[]
     */
    private $taskTypePerformers;

    public function __construct(EntityManagerInterface $entityManager, EventDispatcherInterface $eventDispatcher)
    {
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function addTaskTypePerformer(string $taskTypeName, TaskTypePerformerInterface $taskTypePerformer)
    {
        $this->taskTypePerformers[strtolower($taskTypeName)] = $taskTypePerformer;
    }

    public function perform(Task $task)
    {
        $taskTypePerformer = $this->taskTypePerformers[strtolower($task->getType())];

        /** @noinspection PhpUnhandledExceptionInspection */
        $task->setStartDateTime(new \DateTime());
        $task->setState(Task::STATE_IN_PROGRESS);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $response = $taskTypePerformer->perform($task);

        $this->eventDispatcher->dispatch(TaskEvent::TYPE_PERFORMED, new TaskEvent($task));

        /** @noinspection PhpUnhandledExceptionInspection */
        $task->setEndDateTime(new \DateTime());

        $taskOutput = $task->getOutput();
        if (null === $taskOutput) {
            $task->setOutput($response->getTaskOutput());
        }

        $incompleteStates = [
            Task::STATE_QUEUED,
            Task::STATE_PREPARING,
            Task::STATE_PREPARED,
            Task::STATE_IN_PROGRESS,
        ];

        if (in_array($task->getState(), $incompleteStates)) {
            $task->setState($this->getCompletionStateFromResponse($response));
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();
    }

    private function getCompletionStateFromResponse(TaskTypePerformerResponse $response): string
    {
        if ($response->hasBeenSkipped()) {
            return Task::STATE_SKIPPED;
        }

        if ($response->hasSucceeded()) {
            return Task::STATE_COMPLETED;
        }

        return Task::STATE_FAILED_NO_RETRY_AVAILABLE;
    }
}
