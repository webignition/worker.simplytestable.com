<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Tasks\Request\Success\WithLimit;

use SimplyTestable\WorkerBundle\Tests\Command\Tasks\Request\Success\SuccessTest;

abstract class WithLimitTest extends SuccessTest {

    protected function getExpectedReturnStatusCode() {
        return 0;
    }

    protected function getExpectedResqueQueueIsEmpty() {
        return true;
    }

    protected function hasLimit() {
        return true;
    }

    protected function getLimit() {
        return $this->getExpectedRequestedTaskCount();
    }


    /**
     * @return int
     */
    private function getExpectedRequestedTaskCount() {
        return ($this->getTasksService()->getWorkerProcessCount() * $this->getTasksService()->getMaxTasksRequestFactor()) - $this->getRequiredCurrentTaskCount();
    }

    public function testRequestedTaskCount() {
        $this->assertEquals($this->getExpectedRequestedTaskCount(), $this->getHttpClientService()->getHistory()->getLastRequest()->getPostFields()->get('limit'));
    }

}
