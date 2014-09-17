<?php

namespace SimplyTestable\WorkerBundle\Tests\Resque\Job\TaskReportCompletion;

use SimplyTestable\WorkerBundle\Tests\Resque\Job\JobTest as BaseJobTest;

class JobTest extends BaseJobTest {

    protected function getArgs() {
        return [
            'id' => 1
        ];
    }


    protected function getExpectedQueue() {
        return 'task-report-completion';
    }

}
