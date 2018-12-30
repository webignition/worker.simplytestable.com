<?php

namespace App\Services;

use App\Event\TaskEvent;
use App\Services\TaskTypePerformer\Factory;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task\Task;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TaskPerformer
{
    private $factory;
    private $entityManager;
    private $eventDispatcher;

    public function __construct(
        Factory $factory,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->factory = $factory;
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function perform(Task $task)
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $task->setStartDateTime(new \DateTime());
        $task->setState(Task::STATE_IN_PROGRESS);
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $taskPerformerCollection = $this->factory->getPerformers($task->getType());

        foreach ($taskPerformerCollection as $taskPerformer) {
            if (empty($task->getOutput())) {
                $taskPerformer->perform($task);
            }
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $task->setEndDateTime(new \DateTime());

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(TaskEvent::TYPE_PERFORMED, new TaskEvent($task));
    }
}
