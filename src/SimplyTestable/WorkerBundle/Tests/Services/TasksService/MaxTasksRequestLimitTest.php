<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TasksService;

class MaxTasksRequestLimitTest extends ServiceTest {

    public function testValueDoesNotDefaultToNull() {
        $this->assertNotNull($this->getService()->getMaxTasksRequestFactor());
    }

    public function testValueIsSetViaConfiguration() {
        $this->assertEquals($this->container->getParameter('max_tasks_request_factor'), $this->getService()->getMaxTasksRequestFactor());
    }

}
