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
        if (isset($this->args['limit'])) {
            return [
                'limit' => $this->args['limit']
            ];
        }

        return [];
    }

    protected function getIdentifier() {
        return 'default';
    }

    protected function handleNonZeroReturnCode($returnCode, $output) {
        $command = $this->getCommand();

        if ($returnCode == $command::RETURN_CODE_TASK_WORKLOAD_EXCEEDS_REQUEST_THRESHOLD) {
            $this->getContainer()->get('logger')->info(get_class($this) . ': task [' . $this->getIdentifier() . '] returned ' . $returnCode);
            return true;
        }

        return parent::handleNonZeroReturnCode($returnCode, $output);
    }
}