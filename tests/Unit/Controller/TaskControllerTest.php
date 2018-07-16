<?php

namespace App\Tests\Unit\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Mockery\MockInterface;
use App\Controller\TaskController;
use App\Request\Task\CancelRequest;
use App\Services\TaskService;
use App\Services\WorkerService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use App\Tests\Factory\MockFactory;

/**
 * @group Controller/TaskController
 */
class TaskControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @throws OptimisticLockException
     */
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
            MockFactory::createResqueQueueService()
        );
    }

    /**
     * @throws OptimisticLockException
     */
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

    /**
     * @throws OptimisticLockException
     */
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

    /**
     * @throws OptimisticLockException
     */
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

        /* @var EntityManagerInterface|MockInterface $entityManager */
        $entityManager = \Mockery::mock(EntityManagerInterface::class);

        return new TaskController(
            $entityManager,
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
