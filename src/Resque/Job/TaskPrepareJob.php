<?php

namespace App\Resque\Job;

use App\Command\Task\PrepareCommand;

class TaskPrepareJob extends AbstractTaskCommandJob
{
    const QUEUE_NAME = 'task-prepare';

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
        return $this->getContainer()->get(PrepareCommand::class);
    }
}
