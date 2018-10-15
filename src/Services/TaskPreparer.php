<?php

namespace App\Services;

use App\Services\TaskTypePreparer\Factory;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task\Task;

class TaskPreparer
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Factory
     */
    private $taskTypePreparerFactory;

    public function __construct(EntityManagerInterface $entityManager, Factory $taskTypePreparerFactory)
    {
        $this->entityManager = $entityManager;
        $this->taskTypePreparerFactory = $taskTypePreparerFactory;
    }

    public function prepare(Task $task)
    {
        $taskTypePreparer = $this->taskTypePreparerFactory->getPreparer($task->getType());

        if ($taskTypePreparer) {
            $taskTypePreparer->prepare($task);
        }

        $task->setState(Task::STATE_PREPARED);
        $this->entityManager->persist($task);
        $this->entityManager->flush();
    }
}
