<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Unit\Services\Request\Factory\Task;

use App\Entity\Task\Task;
use App\Request\Task\CancelRequest;
use App\Services\Request\Factory\Task\CancelRequestFactory;
use App\Services\TaskService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CancelRequestFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createReturnsNullDataProvider
     */
    public function testCreateReturnsNull(Request $request, TaskService $taskService)
    {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $cancelRequestFactory = new CancelRequestFactory($requestStack, $taskService);
        $cancelRequest = $cancelRequestFactory->create();

        $this->assertNull($cancelRequest);
    }

    public function createReturnsNullDataProvider(): array
    {
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
        ];
    }

    public function testCreateSuccess()
    {
        $task = \Mockery::mock(Task::class);

        $taskService = \Mockery::mock(TaskService::class);

        $taskService
            ->shouldReceive('getById')
            ->with(1)
            ->andReturn($task);

        $request = new Request([], [
            'id' => '1',
        ]);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $cancelRequestFactory = new CancelRequestFactory($requestStack, $taskService);
        $cancelRequest = $cancelRequestFactory->create();

        if ($cancelRequest instanceof CancelRequest) {
            $this->assertSame($task, $cancelRequest->getTask());
        }
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
