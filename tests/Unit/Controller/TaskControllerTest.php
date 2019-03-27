<?php

namespace App\Tests\Unit\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Mockery\MockInterface;
use App\Controller\TaskController;
use App\Request\Task\CancelRequest;
use App\Services\TaskService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Tests\Factory\MockFactory;

/**
 * @group Controller/TaskController
 */
class TaskControllerTest extends \PHPUnit\Framework\TestCase
{
    public function testCancelActionWithInvalidRequest()
    {
        $this->expectException(BadRequestHttpException::class);

        $taskController = $this->createTaskController();

        $taskController->cancelAction(MockFactory::createCancelRequestFactory([
            'create' => [
                'return' => null,
            ],
        ]));
    }

    /**
     * @param array $services
     *
     * @return TaskController
     */
    private function createTaskController($services = [])
    {
        if (!isset($services[TaskService::class])) {
            $services[TaskService::class] = MockFactory::createTaskService();
        }

        /* @var EntityManagerInterface|MockInterface $entityManager */
        $entityManager = \Mockery::mock(EntityManagerInterface::class);

        return new TaskController(
            $entityManager,
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
