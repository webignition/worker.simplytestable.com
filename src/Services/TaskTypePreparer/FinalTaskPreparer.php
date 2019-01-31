<?php

namespace App\Services\TaskTypePreparer;

use App\Entity\Task\Task;
use App\Event\TaskEvent;
use Doctrine\ORM\EntityManagerInterface;

class FinalTaskPreparer
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function __invoke(TaskEvent $taskEvent)
    {
        if (!$taskEvent->isPropagationStopped()) {
            $task = $taskEvent->getTask();

            $task->setState(Task::STATE_PREPARED);
            $this->entityManager->persist($task);
            $this->entityManager->flush();
        }
    }
}
