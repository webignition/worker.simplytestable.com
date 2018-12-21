<?php

namespace App\Services;

use App\Event\TaskEvent;
use App\Services\TaskTypePerformer\TaskPerformerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task\Task;
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
     * @var TaskPerformerInterface[]
     */
    private $taskTypePerformers;

    public function __construct(EntityManagerInterface $entityManager, EventDispatcherInterface $eventDispatcher)
    {
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function addTaskTypePerformer(string $taskTypeName, TaskPerformerInterface $taskTypePerformer)
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

        $taskTypePerformer->perform($task);

        /** @noinspection PhpUnhandledExceptionInspection */
        $task->setEndDateTime(new \DateTime());

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(TaskEvent::TYPE_PERFORMED, new TaskEvent($task));
    }
}
