<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Tasks\Request\Success;

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
        var_dump("HTTP history count: " . $this->getHttpClientService()->getHistory()->count());

        $this->assertTrue(substr_count($this->getHttpClientService()->getHistory()->getLastRequest()->getUrl(), '&limit=' . $this->getExpectedRequestedTaskCount()) > 0);
    }

}
