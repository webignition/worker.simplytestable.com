<?php

namespace Tests\WorkerBundle\Unit\Controller;

use SimplyTestable\WorkerBundle\Controller\TaskController;
use SimplyTestable\WorkerBundle\Request\Task\CancelRequest;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Tests\WorkerBundle\Factory\MockFactory;

/**
 * @group Controller/TaskController
 */
class TaskControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateCollectionActionInMaintenanceReadOnlyMode()
    {
        $taskController = $this->createTaskController([
            WorkerService::class => MockFactory::createWorkerService([
                'isMaintenanceReadOnly' => [
                    'return' => true,
                ],
            ]),
        ]);

        $this->expectException(ServiceUnavailableHttpException::class);

        $taskController->createCollectionAction(
            MockFactory::createCreateRequestCollectionFactory(),
            MockFactory::createTaskFactory(),
            MockFactory::createResqueQueueService(),
            MockFactory::createResqueJobFactory()
        );
    }

    public function testCancelCollectionActionInMaintenanceReadOnlyMode()
    {
        $this->expectException(ServiceUnavailableHttpException::class);

        $taskController = $this->createTaskController([
            WorkerService::class => MockFactory::createWorkerService([
                'isMaintenanceReadOnly' => [
                    'return' => true,
                ],
            ]),
        ]);

        $taskController->cancelAction(MockFactory::createCancelRequestFactory());
    }

    public function testCancelActionWithInvalidRequest()
    {
        $this->expectException(BadRequestHttpException::class);

        $taskController = $this->createTaskController([
            WorkerService::class => MockFactory::createWorkerService([
                'isMaintenanceReadOnly' => [
                    'return' => false,
                ],
            ]),
        ]);

        $cancelRequest = new CancelRequest(null);

        $taskController->cancelAction(MockFactory::createCancelRequestFactory([
            'create' => [
                'return' => $cancelRequest,
            ],
        ]));
    }

    public function testCancelCollectionActionWithInvalidRequest()
    {
        $this->expectException(ServiceUnavailableHttpException::class);

        $taskController = $this->createTaskController([
            WorkerService::class => MockFactory::createWorkerService([
                'isMaintenanceReadOnly' => [
                    'return' => true,
                ],
            ]),
        ]);

        $taskController->cancelCollectionAction(MockFactory::createCancelRequestCollectionFactory());
    }

    /**
     * @param array $services
     *
     * @return TaskController
     */
    private function createTaskController($services = [])
    {
        if (!isset($services[WorkerService::class])) {
            $services[WorkerService::class] = MockFactory::createWorkerService();
        }

        if (!isset($services[TaskService::class])) {
            $services[TaskService::class] = MockFactory::createTaskService();
        }

        return new TaskController(
            $services[WorkerService::class],
            $services[TaskService::class]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
