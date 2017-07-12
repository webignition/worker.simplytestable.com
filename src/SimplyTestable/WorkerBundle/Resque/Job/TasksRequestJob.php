<?php

namespace SimplyTestable\WorkerBundle\Resque\Job;

use SimplyTestable\WorkerBundle\Command\Tasks\RequestCommand;
use SimplyTestable\WorkerBundle\Services\Resque\JobFactory as ResqueJobFactory;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\WorkerBundle\Services\TasksService;
use SimplyTestable\WorkerBundle\Services\WorkerService;

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
        /* @var TasksService $tasksService */
        $tasksService = $this->getContainer()->get($this->args['serviceIds'][0]);

        /* @var WorkerService $workerService */
        $workerService = $this->getContainer()->get($this->args['serviceIds'][1]);

        /* @var ResqueQueueService $resqueQueueService */
        $resqueQueueService = $this->getContainer()->get($this->args['serviceIds'][2]);

        /* @var ResqueJobFactory $resqueJobFactory */
        $resqueJobFactory = $this->getContainer()->get($this->args['serviceIds'][3]);

        return new RequestCommand(
            $tasksService,
            $workerService,
            $resqueQueueService,
            $resqueJobFactory
        );
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
        $command = $this->getCommand();

        if ($returnCode == $command::RETURN_CODE_TASK_WORKLOAD_EXCEEDS_REQUEST_THRESHOLD) {
            $this->getContainer()->get('logger')
                ->info(get_class($this) . ': task [' . $this->getIdentifier() . '] returned ' . $returnCode);
            return true;
        }

        return parent::handleNonZeroReturnCode($returnCode, $output);
    }
}
