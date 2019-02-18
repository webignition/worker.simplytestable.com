<?php

namespace App\Services;

use App\Event\TaskEvent;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task\Task;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TaskPreparer
{
    private $entityManager;
    private $eventDispatcher;

    public function __construct(
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function prepare(Task $task)
    {
        $task->setState(Task::STATE_PREPARING);
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $taskEvent = new TaskEvent($task);
        $this->eventDispatcher->dispatch(TaskEvent::TYPE_PREPARE, $taskEvent);

        $nextEvent = Task::STATE_PREPARED === $task->getState()
            ? TaskEvent::TYPE_PREPARED
            : TaskEvent::TYPE_CREATED;

        $this->eventDispatcher->dispatch($nextEvent, new TaskEvent($task));
    }
}
