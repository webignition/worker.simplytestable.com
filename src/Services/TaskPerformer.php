<?php

namespace App\Services;

use App\Event\TaskEvent;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task\Task;
use App\Model\TaskTypePerformer\Response as TaskTypePerformerResponse;
use App\Services\TaskTypePerformer\TaskTypePerformer;
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
     * @var TaskTypePerformer[]
     */
    private $taskTypePerformers;

    public function __construct(EntityManagerInterface $entityManager, EventDispatcherInterface $eventDispatcher)
    {
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function addTaskTypePerformer(string $taskTypeName, TaskTypePerformer $taskTypePerformer)
    {
        $this->taskTypePerformers[strtolower($taskTypeName)] = $taskTypePerformer;
    }

    public function perform(Task $task)
    {
        $taskTypePerformer = $this->taskTypePerformers[strtolower($task->getType())];

        $task->setStartDateTime(new \DateTime());
        $task->setState(Task::STATE_IN_PROGRESS);

        $response = $taskTypePerformer->perform($task);

        $this->eventDispatcher->dispatch(TaskEvent::TYPE_PERFORMED, new TaskEvent($task));

        if (!$task->getTimePeriod()->hasEndDateTime()) {
            $task->getTimePeriod()->setEndDateTime(new \DateTime());
        }

        $task->setOutput($response->getTaskOutput());
        $task->setState($this->getCompletionStateFromResponse($response));

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
