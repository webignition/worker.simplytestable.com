<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Tasks\Request\Success;

abstract class AboveThresholdTest extends SuccessTest {

    protected function getExpectedReturnStatusCode() {
        return 1;
    }

    protected function getExpectedResqueQueueIsEmpty() {
        return false;
    }

}
