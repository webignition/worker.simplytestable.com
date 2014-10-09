<?php

namespace SimplyTestable\WorkerBundle\Tests\Controller\Tasks\NotifyAction;

use SimplyTestable\WorkerBundle\Tests\Controller\Tasks\ControllerTest as BaseControllerTest;

class ControllerTest extends BaseControllerTest {

    /**
     * @var \Symfony\Component\HttpFoundation\Response
     */
    private $response;

    public function setUp() {
        parent::setUp();

        $this->clearRedis();

        $this->response = $this->getTasksController('notifyAction')->notifyAction();
    }


    public function testResponseStatusCode() {
        $this->assertEquals(200, $this->response->getStatusCode());
    }


    public function testResqueJobIsCreated() {
        $this->assertFalse($this->getRequeQueueService()->isEmpty('tasks-request'));
    }


}


