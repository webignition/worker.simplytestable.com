<?php

namespace SimplyTestable\WorkerBundle\Resque\Job;

use SimplyTestable\WorkerBundle\Command\Task\PerformCommand;

class TaskPerformJob extends CommandJob
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
    protected function getCommandName()
    {
        return PerformCommand::NAME;
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
