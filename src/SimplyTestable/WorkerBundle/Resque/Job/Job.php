<?php

namespace SimplyTestable\WorkerBundle\Resque\Job;

use BCC\ResqueBundle\Job as BaseJob;

abstract class Job extends BaseJob {

    public function __construct($args = []) {
        $this->args = $args;
    }


    public function setQueue($queue) {
        $this->queue = $queue;
    }

}