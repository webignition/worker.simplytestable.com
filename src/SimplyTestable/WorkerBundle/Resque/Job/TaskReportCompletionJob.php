<?php

namespace SimplyTestable\WorkerBundle\Resque\Job;

use SimplyTestable\WorkerBundle\Exception\TaskReportCompletionException;

class TaskReportCompletionJob extends CommandLineJob {    
    
    const QUEUE_NAME = 'task-perform';
    const COMMAND = 'php app/console simplytestable:task:reportcompletion';
    
    protected function getQueueName() {
        return self::QUEUE_NAME;
    }
    
    protected function getArgumentOrder() {
        return array('id');
    }
    
    protected function getCommand() {
        return self::COMMAND;
    }
    
    protected function failureHandler($output, $returnValue) {
        throw new TaskReportCompletionException(implode("\n", $output), $returnValue);
    }   
}