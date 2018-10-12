<?php

namespace App\Resque\Job;

use App\Command\Task\PerformCommand;

class TaskPerformJob extends AbstractTaskCommandJob
{
    const QUEUE_NAME = 'task-perform';

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
        return $this->getContainer()->get(PerformCommand::class);
    }
}
