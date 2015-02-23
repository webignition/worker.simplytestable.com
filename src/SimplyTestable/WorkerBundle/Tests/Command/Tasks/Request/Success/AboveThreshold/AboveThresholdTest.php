<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Tasks\Request\Success\AboveThreshold;

use SimplyTestable\WorkerBundle\Tests\Command\Tasks\Request\Success\SuccessTest;

abstract class AboveThresholdTest extends SuccessTest {

    protected function getExpectedReturnStatusCode() {
        return 3;
    }

    protected function getExpectedResqueQueueIsEmpty() {
        return false;
    }

}
