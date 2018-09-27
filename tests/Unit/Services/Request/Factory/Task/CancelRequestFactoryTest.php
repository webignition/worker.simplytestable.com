<?php

namespace App\Tests\Unit\Services\Request\Factory\Task;

use Mockery\MockInterface;
use App\Entity\Task\Task;
use App\Services\Request\Factory\Task\CancelRequestFactory;
use App\Services\TaskService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CancelRequestFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param Request $request
     * @param TaskService $taskService,
     * @param Task $expectedTask
     */
    public function testCreate(
        Request $request,
        TaskService $taskService,
        $expectedTask
    ) {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $cancelRequestFactory = new CancelRequestFactory($requestStack, $taskService);
        $cancelRequest = $cancelRequestFactory->create();

        $this->assertEquals($expectedTask, $cancelRequest->getTask());
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        $task = new Task();

        return [
            'empty task' => [
                'request' => new Request(),
                'taskService' => \Mockery::mock(TaskService::class),
                'expectedTask' => null,
            ],
            'invalid task' => [
                'request' => new Request([], [
                    'id' => 'foo',
                ]),
                'taskService' => \Mockery::mock(TaskService::class),
                'expectedTaskType' => null,
            ],
            'valid task' => [
                'request' => new Request([], [
                    'id' => '1',
                ]),
                'taskService' => $this->createTaskService($task),
                'expectedTaskType' => $task,
            ],
        ];
    }

    /**
     * @param Task $task
     *
     * @return MockInterface|TaskService
     */
    private function createTaskService(Task $task)
    {
        $taskService = \Mockery::mock(TaskService::class);

        $taskService
            ->shouldReceive('getById')
            ->with(1)
            ->andReturn($task);

        return $taskService;
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
