<?php

namespace App\Tests\Functional\Controller;

use App\Services\WorkerService;

class MaintenanceControllerTest extends AbstractControllerTest
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

        $this->workerService = self::$container->get(WorkerService::class);
    }

    public function testEnableReadOnlyAction()
    {
        $this->client->request('GET', $this->router->generate('enable_readonly'));

        $worker = $this->workerService->get();

        $this->assertSuccessResponse('["Set state to maintenance-read-only"]');
        $this->assertTrue($worker->isMaintenanceReadOnly());
        $this->assertFalse($worker->isActive());
    }

    public function testDisableReadOnlyAction()
    {
        $this->client->request('GET', $this->router->generate('disable_readonly'));

        $worker = $this->workerService->get();

        $this->assertSuccessResponse('["Set state to active"]');
        $this->assertFalse($worker->isMaintenanceReadOnly());
        $this->assertTrue($worker->isActive());
    }

    public function testTaskPerformEnqueueAction()
    {
        $this->client->request('GET', $this->router->generate('task_perform_enqueue'));

        $this->assertSuccessResponse('["0 queued tasks ready to be enqueued"]');
    }

    public function testLeaveReadOnlyAction()
    {
        $this->client->request('GET', $this->router->generate('leave_readonly'));

        $worker = $this->workerService->get();

        $this->assertSuccessResponse(
            '["Set state to active","0 completed tasks ready to be enqueued","0 queued tasks ready to be enqueued"]'
        );
        $this->assertFalse($worker->isMaintenanceReadOnly());
        $this->assertTrue($worker->isActive());
    }

    /**
     * @param string $expectedContent
     */
    private function assertSuccessResponse($expectedContent)
    {
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(
            $expectedContent,
            $response->getContent()
        );
    }
}
