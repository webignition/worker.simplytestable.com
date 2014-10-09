<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TasksService\Request\Success;

class NoSetLimitTest extends SuccessTest {

    protected function getExpectedRequestedTaskCount() {
        return $this->container->getParameter('worker_process_count') * $this->container->getParameter('max_tasks_request_factor');
    }

}
