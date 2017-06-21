<?php

namespace SimplyTestable\WorkerBundle\Tests\Unit\Request\Task;

use Mockery\MockInterface;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CancelRequestFactory;
use SimplyTestable\WorkerBundle\Services\TaskService;
use Symfony\Component\HttpFoundation\Request;

class CancelRequestFactoryTest extends \PHPUnit_Framework_TestCase
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
        $cancelRequestFactory = new CancelRequestFactory($request, $taskService);
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
     * @return MockInterface|TaskService
     */
    private function createTaskService($task)
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
