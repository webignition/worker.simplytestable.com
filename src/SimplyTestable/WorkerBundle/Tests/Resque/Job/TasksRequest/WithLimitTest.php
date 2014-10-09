<?php

namespace SimplyTestable\WorkerBundle\Tests\Resque\Job\TasksRequest;

use SimplyTestable\WorkerBundle\Tests\Resque\Job\JobTest as BaseJobTest;

class WithLimitTest extends BaseJobTest {

    protected function getArgs() {
        return [
            'limit' => 5
        ];
    }


    protected function getExpectedQueue() {
        return 'tasks-request';
    }

}
