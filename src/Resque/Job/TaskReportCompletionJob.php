<?php

namespace App\Resque\Job;

use App\Command\Task\ReportCompletionCommand;

class TaskReportCompletionJob extends AbstractTaskCommandJob
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
        return $this->getContainer()->get(ReportCompletionCommand::class);
    }
}
