<?php

namespace SimplyTestable\WorkerBundle\Resque\Job;

use SimplyTestable\WorkerBundle\Command\Task\ReportCompletionCommand;

class TaskReportCompletionJob extends CommandJob {

    const QUEUE_NAME = 'task-report-completion';

    protected function getQueueName() {
        return self::QUEUE_NAME;
    }

    protected function getCommand() {
        return new ReportCompletionCommand();
    }
}