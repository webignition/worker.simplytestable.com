<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TasksService;

class RequestLimitTest extends ServiceTest {

    public function testRequestLimitIsSetViaConfiguration() {
        $this->assertNotNull($this->getService()->getTaskRequestLimit());
    }

}
