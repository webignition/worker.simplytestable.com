<?php

namespace Tests\WorkerBundle\Functional\Controller;

use SimplyTestable\WorkerBundle\Command\Maintenance\DisableReadOnlyCommand;
use SimplyTestable\WorkerBundle\Command\Maintenance\EnableReadOnlyCommand;
use SimplyTestable\WorkerBundle\Command\Task\PerformEnqueueCommand;
use SimplyTestable\WorkerBundle\Command\Task\ReportCompletionEnqueueCommand;
use SimplyTestable\WorkerBundle\Controller\MaintenanceController;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;

class MaintenanceControllerTest extends BaseSimplyTestableTestCase
{
    /**
     * @var WorkerService
     */
    private $workerService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->workerService = $this->container->get(WorkerService::class);
    }

    public function testEnableReadOnlyAction()
    {
        $response = $this->createMaintenanceController()->enableReadOnlyAction(
            $this->container->get(EnableReadOnlyCommand::class)
        );

        $this->assertEquals(
            '["Set state to maintenance-read-only"]',
            $response->getContent()
        );
        $this->assertTrue($this->workerService->isMaintenanceReadOnly());
        $this->assertFalse($this->workerService->isActive());
    }

    public function testDisableReadOnlyAction()
    {
        $response = $this->createMaintenanceController()->disableReadOnlyAction(
            $this->container->get(DisableReadOnlyCommand::class)
        );

        $this->assertEquals(
            '["Set state to active"]',
            $response->getContent()
        );
        $this->assertFalse($this->workerService->isMaintenanceReadOnly());
        $this->assertTrue($this->workerService->isActive());
    }

    public function testTaskPerformEnqueueAction()
    {
        $response = $this->createMaintenanceController()->taskPerformEnqueueAction(
            $this->container->get(PerformEnqueueCommand::class)
        );

        $this->assertEquals(
            '["0 queued tasks ready to be enqueued"]',
            $response->getContent()
        );
    }

    public function testLeaveReadOnlyAction()
    {
        $this->removeAllTasks();

        $response = $this->createMaintenanceController()->leaveReadOnlyAction(
            $this->container->get(DisableReadOnlyCommand::class),
            $this->container->get(ReportCompletionEnqueueCommand::class),
            $this->container->get(PerformEnqueueCommand::class)
        );

        $this->assertEquals(
            '["Set state to active","0 completed tasks ready to be enqueued","0 queued tasks ready to be enqueued"]',
            $response->getContent()
        );
        $this->assertFalse($this->workerService->isMaintenanceReadOnly());
        $this->assertTrue($this->workerService->isActive());
    }

    /**
     * @return MaintenanceController
     */
    private function createMaintenanceController()
    {
        $controller = new MaintenanceController();

        return $controller;
    }
}
