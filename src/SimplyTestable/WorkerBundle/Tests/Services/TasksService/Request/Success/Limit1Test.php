<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TasksService\Request\Success;

class Limit1Test extends SuccessTest {

    protected function getTaskRequestLimit() {
        return 1;
    }

    protected function getExpectedRequestedTaskCount() {
        return $this->getTaskRequestLimit();
    }

}
