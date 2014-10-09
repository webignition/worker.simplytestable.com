<?php

namespace SimplyTestable\WorkerBundle\Tests\Resque\Job\TasksRequest;

use SimplyTestable\WorkerBundle\Tests\Resque\Job\JobTest as BaseJobTest;

class NoLimitTest extends BaseJobTest {

    protected function getArgs() {
        return [];
    }


    protected function getExpectedQueue() {
        return 'tasks-request';
    }

}
