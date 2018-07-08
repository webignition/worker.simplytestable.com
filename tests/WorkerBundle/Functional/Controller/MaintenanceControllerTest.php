<?php

namespace Tests\WorkerBundle\Functional\Controller;

use SimplyTestable\WorkerBundle\Command\Maintenance\DisableReadOnlyCommand;
use SimplyTestable\WorkerBundle\Command\Maintenance\EnableReadOnlyCommand;
use SimplyTestable\WorkerBundle\Command\Task\PerformEnqueueCommand;
use SimplyTestable\WorkerBundle\Command\Task\ReportCompletionEnqueueCommand;
use SimplyTestable\WorkerBundle\Controller\MaintenanceController;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Tests\WorkerBundle\Functional\AbstractBaseTestCase;

class MaintenanceControllerTest extends AbstractBaseTestCase
{
    /**
     * @var MaintenanceController
     */
    private $maintenanceController;

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

        $this->maintenanceController = new MaintenanceController();
        $this->workerService = $this->container->get(WorkerService::class);
    }

    public function testEnableReadOnlyAction()
    {
        $response = $this->maintenanceController->enableReadOnlyAction(
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
        $response = $this->maintenanceController->disableReadOnlyAction(
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
        $response = $this->maintenanceController->taskPerformEnqueueAction(
            $this->container->get(PerformEnqueueCommand::class)
        );

        $this->assertEquals(
            '["0 queued tasks ready to be enqueued"]',
            $response->getContent()
        );
    }

    public function testLeaveReadOnlyAction()
    {
        $response = $this->maintenanceController->leaveReadOnlyAction(
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
}
