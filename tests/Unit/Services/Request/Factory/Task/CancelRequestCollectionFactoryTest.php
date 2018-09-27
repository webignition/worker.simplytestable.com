<?php

namespace App\Tests\Unit\Services\Request\Factory\Task;

use Mockery\MockInterface;
use App\Entity\Task\Task;
use App\Services\Request\Factory\Task\CancelRequestCollectionFactory;
use App\Services\Request\Factory\Task\CancelRequestFactory;
use App\Services\TaskService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CancelRequestCollectionFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param Request $request
     * @param int $expectedCollectionCount
     */
    public function testCreate(Request $request, $expectedCollectionCount)
    {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $task = new Task();

        /* @var TaskService|MockInterface $taskService */
        $taskService = \Mockery::mock(TaskService::class);

        $taskService
            ->shouldReceive('getById')
            ->with('1')
            ->andReturn($task);

        $taskService
            ->shouldReceive('getById')
            ->with('2')
            ->andReturn(null);

        $cancelRequestFactory = new CancelRequestFactory($requestStack, $taskService);
        $cancelRequestCollectionFactory = new CancelRequestCollectionFactory($requestStack, $cancelRequestFactory);
        $cancelRequestCollection = $cancelRequestCollectionFactory->create();

        $this->assertCount($expectedCollectionCount, $cancelRequestCollection->getCancelRequests());
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'no requests' => [
                'request' => new Request(),
                'expectedCollectionCount' => 0,
            ],
            'task ids not array' => [
                'request' => new Request([], [
                    'ids' => '',
                ]),
                'expectedCollectionCount' => 0,
            ],
            'one valid task' => [
                'request' => new Request([], [
                    'ids' => [1, 2],
                ]),
                'expectedCollectionCount' => 1,
            ],
        ];
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
