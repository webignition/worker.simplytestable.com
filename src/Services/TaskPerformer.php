<?php

namespace App\Services;

use App\Event\TaskEvent;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task\Task;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TaskPerformer
{
    private $entityManager;
    private $eventDispatcher;

    public function __construct(EntityManagerInterface $entityManager, EventDispatcherInterface $eventDispatcher)
    {
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function perform(Task $task)
    {
        if (empty($task->getStartDateTime())) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $task->setStartDateTime(new \DateTime());
            $task->setState(Task::STATE_IN_PROGRESS);
            $this->entityManager->persist($task);
            $this->entityManager->flush();
        }

        $taskEvent = new TaskEvent($task);
        $this->eventDispatcher->dispatch(TaskEvent::TYPE_PERFORM, $taskEvent);

        $nextEvent = TaskEvent::TYPE_PERFORMED;

        if ($task->isIncomplete()) {
            $nextEvent = TaskEvent::TYPE_PREPARED;
        } else {
            /** @noinspection PhpUnhandledExceptionInspection */
            $task->setEndDateTime(new \DateTime());
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch($nextEvent, new TaskEvent($task));
    }
}
