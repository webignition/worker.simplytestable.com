<?php

namespace SimplyTestable\WorkerBundle\Resque\Job;

use SimplyTestable\WorkerBundle\Command\Tasks\RequestCommand;

class TasksRequestJob extends CommandJob {

    const QUEUE_NAME = 'tasks-request';

    protected function getQueueName() {
        return self::QUEUE_NAME;
    }

    protected function getCommand() {
        return new RequestCommand();
    }

    protected function getCommandArgs() {
        return [];
    }
}