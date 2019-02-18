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

        $nextEvent = TaskEvent::TYPE_PREPARED;

        if ($task->isIncomplete()) {
            if (Task::STATE_PREPARED !== $task->getState()) {
                $nextEvent = TaskEvent::TYPE_CREATED;
            }
        } else {
            if (empty($task->getStartDateTime())) {
                /** @noinspection PhpUnhandledExceptionInspection */
                $task->setStartDateTime(new \DateTime());
            }

            /** @noinspection PhpUnhandledExceptionInspection */
            $task->setEndDateTime(new \DateTime());

            $nextEvent = TaskEvent::TYPE_PERFORMED;
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch($nextEvent, new TaskEvent($task));
    }
}
