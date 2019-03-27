<?php

namespace App\Services;

use App\Event\TaskEvent;
use App\Exception\UnableToPerformTaskException;
use App\Exception\UnableToRetrieveResourceException;
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

        $nextEvent = TaskEvent::TYPE_PREPARED;

        try {
            $this->eventDispatcher->dispatch(TaskEvent::TYPE_PREPARE, $taskEvent);

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
        } catch (UnableToRetrieveResourceException $unableToRetrieveResourceException) {
            $nextEvent = TaskEvent::TYPE_CREATED;
        } catch (UnableToPerformTaskException $unableToPerformTaskException) {
            $nextEvent = TaskEvent::TYPE_CREATED;
            $task->reset();
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch($nextEvent, new TaskEvent($task));
    }
}
