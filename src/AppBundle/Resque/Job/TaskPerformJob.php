<?php

namespace AppBundle\Resque\Job;

use AppBundle\Command\Task\PerformCommand;

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
    protected function getCommand()
    {
        return $this->getContainer()->get(PerformCommand::class);
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
