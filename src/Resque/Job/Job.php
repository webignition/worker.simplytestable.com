<?php

namespace App\Resque\Job;

use ResqueBundle\Resque\ContainerAwareJob;

abstract class Job extends ContainerAwareJob
{
    abstract protected function getQueueName();

    public function __construct(array $args = [])
    {
        parent::__construct($args);

        $this->setQueue($this->getQueueName());
    }

    public function setQueue($queue)
    {
        $this->queue = $queue;
    }
}
