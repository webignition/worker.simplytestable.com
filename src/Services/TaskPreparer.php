<?php

namespace App\Services;

use App\Event\TaskEvent;
use App\Services\TaskTypePreparer\Factory;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task\Task;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TaskPreparer
{
    private $entityManager;
    private $taskTypePreparerFactory;
    private $eventDispatcher;

    public function __construct(
        EntityManagerInterface $entityManager,
        Factory $taskTypePreparerFactory,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->taskTypePreparerFactory = $taskTypePreparerFactory;
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

        $this->eventDispatcher->dispatch($nextEvent, $taskEvent);
    }
}
