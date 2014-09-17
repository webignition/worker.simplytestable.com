<?php

namespace SimplyTestable\WorkerBundle\Resque\Job;

use SimplyTestable\WorkerBundle\Command\Task\PerformCommand;

class TaskPerformJob extends CommandJob {

    const QUEUE_NAME = 'task-perform';

    protected function getQueueName() {
        return self::QUEUE_NAME;
    }

    protected function getCommand() {
        return new PerformCommand();
    }

    protected function getCommandArgs() {
        return [
            $this->args['id']
        ];
    }

}