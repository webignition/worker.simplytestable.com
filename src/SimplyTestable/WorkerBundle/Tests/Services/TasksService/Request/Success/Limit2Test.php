<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TasksService\Request\Success;

class Limit2Test extends SuccessTest {

    protected function getTaskRequestLimit() {
        return 2;
    }

    protected function getExpectedRequestedTaskCount() {
        return $this->getTaskRequestLimit();
    }

}
