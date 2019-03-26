<?php

namespace App\EventListener;

use App\Event\AbstractTaskReportCompletionEvent;
use App\Event\TaskReportCompletionFailureEvent;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class TaskReportedCompletionEventListener
{
    private $entityManager;
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function __invoke(AbstractTaskReportCompletionEvent $taskReportCompletionEvent)
    {
        $task = $taskReportCompletionEvent->getTask();

        if ($taskReportCompletionEvent->isSucceeded()) {
            $this->entityManager->remove($task);
            $this->entityManager->remove($task->getOutput());
            $this->entityManager->flush();
        }

        if ($taskReportCompletionEvent instanceof TaskReportCompletionFailureEvent) {
            $this->logger->error(
                'task-report-completion failed: [' . $task->getId() . ']',
                [
                    'request_url' => $taskReportCompletionEvent->getRequestUrl(),
                    'failure_type' => $taskReportCompletionEvent->getFailureType(),
                    'status_code' => $taskReportCompletionEvent->getStatusCode(),
                ]
            );
        }
    }
}
