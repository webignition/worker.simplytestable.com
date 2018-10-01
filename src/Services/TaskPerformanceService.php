<?php

namespace App\Services;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task\Task;
use App\Entity\TimePeriod;
use App\Model\TaskDriver\Response as TaskDriverResponse;
use App\Services\TaskDriver\TaskDriver;

class TaskPerformanceService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TaskDriver[]
     */
    private $taskDrivers;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function addTaskDriver(string $taskTypeName, TaskDriver $taskDriver)
    {
        $this->taskDrivers[strtolower($taskTypeName)] = $taskDriver;
    }

    public function perform(Task $task)
    {
        $taskDriver = $this->taskDrivers[strtolower($task->getType())];

        $this->start($task);

        $taskDriverResponse = $taskDriver->perform($task);

        if (!$task->getTimePeriod()->hasEndDateTime()) {
            $task->getTimePeriod()->setEndDateTime(new \DateTime());
        }

        $task->setOutput($taskDriverResponse->getTaskOutput());
        $task->setState($this->getCompletionStateFromTaskDriverResponse($taskDriverResponse));

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

    private function getCompletionStateFromTaskDriverResponse(TaskDriverResponse $taskDriverResponse): string
    {
        if ($taskDriverResponse->hasBeenSkipped()) {
            return Task::STATE_SKIPPED;
        }

        if ($taskDriverResponse->hasSucceeded()) {
            return Task::STATE_COMPLETED;
        }

        return Task::STATE_FAILED_NO_RETRY_AVAILABLE;
    }
}
