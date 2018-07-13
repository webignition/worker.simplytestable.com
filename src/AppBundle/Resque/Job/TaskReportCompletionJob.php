<?php

namespace AppBundle\Resque\Job;

use AppBundle\Command\Task\ReportCompletionCommand;

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
        return $this->getContainer()->get(ReportCompletionCommand::class);
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
