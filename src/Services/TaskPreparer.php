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

        $taskTypePreparer = $this->taskTypePreparerFactory->getPreparer($task->getType());

        if ($taskTypePreparer) {
            $taskTypePreparer->prepare($task);
        } else {
            $this->eventDispatcher->dispatch(TaskEvent::TYPE_PREPARED, new TaskEvent($task));

            $task->setState(Task::STATE_PREPARED);
            $this->entityManager->persist($task);
            $this->entityManager->flush();
        }
    }
}
