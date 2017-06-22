<?php

namespace SimplyTestable\WorkerBundle\Resque\Job;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use SimplyTestable\WorkerBundle\Command\Task\ReportCompletionCommand;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\WorkerService;

class TaskReportCompletionJob extends CommandJob
{
    const QUEUE_NAME = 'task-report-completion';

    /**
     * {@inheritdoc}
     */
    protected function getQueueName()
    {
        return self::QUEUE_NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommand()
    {
        /* @var LoggerInterface $logger */
        $logger = $this->getContainer()->get($this->args['serviceIds'][0]);

        /* @var TaskService $taskService */
        $taskService = $this->getContainer()->get($this->args['serviceIds'][1]);

        /* @var WorkerService $workerService */
        $workerService = $this->getContainer()->get($this->args['serviceIds'][2]);

        /* @var EntityManager $entityManager */
        $entityManager = $this->getContainer()->get($this->args['serviceIds'][3]);

        return new ReportCompletionCommand(
            $logger,
            $taskService,
            $workerService,
            $entityManager
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandArgs()
    {
        return [
            'id' => $this->args['id']
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getIdentifier()
    {
        return $this->args['id'];
    }
}
