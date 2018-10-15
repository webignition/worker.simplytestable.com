<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task\Task;
use App\Entity\TimePeriod;
use App\Model\TaskTypePerformer\Response as TaskTypePerformerResponse;
use App\Services\TaskTypePerformer\TaskTypePerformer;

class TaskPerformer
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TaskTypePerformer[]
     */
    private $taskTypePerformers;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function addTaskTypePerformer(string $taskTypeName, TaskTypePerformer $taskTypePerformer)
    {
        $this->taskTypePerformers[strtolower($taskTypeName)] = $taskTypePerformer;
    }

    public function perform(Task $task)
    {
        $taskTypePerformer = $this->taskTypePerformers[strtolower($task->getType())];

        $this->start($task);

        $response = $taskTypePerformer->perform($task);

        if (!$task->getTimePeriod()->hasEndDateTime()) {
            $task->getTimePeriod()->setEndDateTime(new \DateTime());
        }

        $task->setOutput($response->getTaskOutput());
        $task->setState($this->getCompletionStateFromResponse($response));

        $this->entityManager->persist($task);
        $this->entityManager->flush();
    }

    private function start(Task $task)
    {
        $timePeriod = new TimePeriod();
        $timePeriod->setStartDateTime(new \DateTime());
        $task->setTimePeriod($timePeriod);
        $task->setState(Task::STATE_IN_PROGRESS);

        $this->entityManager->persist($task);
        $this->entityManager->flush();
    }

    private function getCompletionStateFromResponse(TaskTypePerformerResponse $response): string
    {
        if ($response->hasBeenSkipped()) {
            return Task::STATE_SKIPPED;
        }

        if ($response->hasSucceeded()) {
            return Task::STATE_COMPLETED;
        }

        return Task::STATE_FAILED_NO_RETRY_AVAILABLE;
    }
}
