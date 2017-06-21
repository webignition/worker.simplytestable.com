<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Controller;

use SimplyTestable\WorkerBundle\Controller\MaintenanceController;
use SimplyTestable\WorkerBundle\Tests\Functional\BaseSimplyTestableTestCase;

class MaintenanceControllerTest extends BaseSimplyTestableTestCase
{
    public function testEnableReadOnlyAction()
    {
        $response = $this->createMaintenanceController()->enableReadOnlyAction();

        $this->assertEquals(
            '["Set state to maintenance-read-only"]',
            $response->getContent()
        );
        $this->assertTrue($this->getWorkerService()->isMaintenanceReadOnly());
        $this->assertFalse($this->getWorkerService()->isActive());
    }

    public function testDisableReadOnlyAction()
    {
        $response = $this->createMaintenanceController()->disableReadOnlyAction();

        $this->assertEquals(
            '["Set state to active"]',
            $response->getContent()
        );
        $this->assertFalse($this->getWorkerService()->isMaintenanceReadOnly());
        $this->assertTrue($this->getWorkerService()->isActive());
    }

    public function testLeaveReadOnlyAction()
    {
        $this->removeAllTasks();

        $response = $this->createMaintenanceController()->leaveReadOnlyAction();

        $this->assertEquals(
            '["Set state to active","0 completed tasks ready to be enqueued","0 queued tasks ready to be enqueued"]',
            $response->getContent()
        );
        $this->assertFalse($this->getWorkerService()->isMaintenanceReadOnly());
        $this->assertTrue($this->getWorkerService()->isActive());
    }

    /**
     * @return MaintenanceController
     */
    private function createMaintenanceController()
    {
        $controller = new MaintenanceController();
        $controller->setContainer($this->container);

        return $controller;
    }
}
