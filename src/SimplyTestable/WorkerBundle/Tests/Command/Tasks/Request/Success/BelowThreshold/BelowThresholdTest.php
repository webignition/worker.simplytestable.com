<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Tasks\Request\Success\BelowThreshold;

use SimplyTestable\WorkerBundle\Tests\Command\Tasks\Request\Success\SuccessTest;

abstract class BelowThresholdTest extends SuccessTest {

    protected function getExpectedReturnStatusCode() {
        return 0;
    }

    protected function getExpectedResqueQueueIsEmpty() {
        return true;
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
