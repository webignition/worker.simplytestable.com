<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TasksService;

class WorkerProcessCountTest extends ServiceTest {

    public function testValueDoesNotDefaultToNull() {
        $this->assertNotNull($this->getService()->getWorkerProcessCount());
    }

    public function testValueIsSetViaConfiguration() {
        $this->assertEquals($this->container->getParameter('worker_process_count'), $this->getService()->getWorkerProcessCount());
    }

}
