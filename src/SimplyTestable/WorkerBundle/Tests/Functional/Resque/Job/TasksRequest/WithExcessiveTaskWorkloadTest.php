<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Resque\Job\TasksRequest;

use SimplyTestable\WorkerBundle\Tests\Functional\Resque\Job\JobTest as BaseJobTest;

class WithExcessiveTaskWorkloadTest extends BaseJobTest {

    protected function getArgs() {
        return [
            'limit' => 5,
            'returnCode' => 3
        ];
    }


    protected function getExpectedQueue() {
        return 'tasks-request';
    }

}
