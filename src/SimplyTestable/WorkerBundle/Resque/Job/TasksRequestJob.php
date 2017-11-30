<?php

namespace SimplyTestable\WorkerBundle\Resque\Job;

use SimplyTestable\WorkerBundle\Command\Tasks\RequestCommand;

class TasksRequestJob extends CommandJob
{
    const QUEUE_NAME = 'tasks-request';

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
        return $this->getContainer()->get(RequestCommand::class);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandArgs()
    {
        if (isset($this->args['limit'])) {
            return [
                'limit' => $this->args['limit']
            ];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function getIdentifier()
    {
        return 'default';
    }

    /**
     * {@inheritdoc}
     */
    protected function handleNonZeroReturnCode($returnCode, $output)
    {
        if ($returnCode == RequestCommand::RETURN_CODE_TASK_WORKLOAD_EXCEEDS_REQUEST_THRESHOLD) {
            $this->getContainer()->get('logger')
                ->info(get_class($this) . ': task [' . $this->getIdentifier() . '] returned ' . $returnCode);
            return true;
        }

        return parent::handleNonZeroReturnCode($returnCode, $output);
    }
}
