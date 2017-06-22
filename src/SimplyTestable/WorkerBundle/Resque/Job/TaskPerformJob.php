<?php

namespace SimplyTestable\WorkerBundle\Resque\Job;

use Psr\Log\LoggerInterface;
use SimplyTestable\WorkerBundle\Command\Task\PerformCommand;
use SimplyTestable\WorkerBundle\Services\Resque\JobFactory as ResqueJobFactory;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService as ResqueQueueService;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\WorkerService;

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
        /* @var LoggerInterface $logger */
        $logger = $this->getContainer()->get($this->args['serviceIds'][0]);

        /* @var TaskService $taskService */
        $taskService = $this->getContainer()->get($this->args['serviceIds'][1]);

        /* @var WorkerService $workerService */
        $workerService = $this->getContainer()->get($this->args['serviceIds'][2]);

        /* @var ResqueQueueService $resqueQueueService */
        $resqueQueueService = $this->getContainer()->get($this->args['serviceIds'][3]);

        /* @var ResqueJobFactory $resqueJobFactory */
        $resqueJobFactory = $this->getContainer()->get($this->args['serviceIds'][4]);

        return new PerformCommand(
            $logger,
            $taskService,
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
