<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Resque\Job\TaskReportCompletion;

use SimplyTestable\WorkerBundle\Tests\Functional\Resque\Job\JobTest as BaseJobTest;

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
