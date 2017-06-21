<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Resque\Job\TasksRequest;

use SimplyTestable\WorkerBundle\Tests\Functional\Resque\Job\JobTest as BaseJobTest;

class NoLimitTest extends BaseJobTest {

    protected function getArgs() {
        return [];
    }


    protected function getExpectedQueue() {
        return 'tasks-request';
    }

}
