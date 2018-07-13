<?php

namespace Tests\AppBundle\Unit\Request\Task;

use Mockery\MockInterface;
use AppBundle\Services\Request\Factory\Task\CreateRequestCollectionFactory;
use AppBundle\Services\Request\Factory\Task\CreateRequestFactory;
use AppBundle\Services\TaskTypeService;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Task\Type\Type as TaskType;
use Symfony\Component\HttpFoundation\RequestStack;

class CreateRequestCollectionFactoryTest extends \PHPUnit\Framework\TestCase
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

        $taskType = new TaskType();
        $taskType->setName('foo');

        /* @var TaskTypeService|MockInterface $taskTypeService */
        $taskTypeService = \Mockery::mock(TaskTypeService::class);

        $taskTypeService
            ->shouldReceive('fetch')
            ->with('foo')
            ->andReturn($taskType);

        $taskTypeService
            ->shouldReceive('fetch')
            ->with('bar')
            ->andReturn(null);

        $createRequestFactory = new CreateRequestFactory($requestStack, $taskTypeService);
        $createRequestCollectionFactory = new CreateRequestCollectionFactory($requestStack, $createRequestFactory);
        $createRequestCollection = $createRequestCollectionFactory->create();

        $this->assertCount($expectedCollectionCount, $createRequestCollection->getCreateRequests());
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
            'request tasks not array' => [
                'request' => new Request([], [
                    'tasks' => '',
                ]),
                'expectedCollectionCount' => 0,
            ],
            'one valid task' => [
                'request' => new Request([], [
                    'tasks' => [
                        [
                            'type' => 'foo',
                            'url' => 'http://example.com/',
                        ],
                        [
                            'type' => 'bar',
                            'url' => 'http://example.com/',
                        ],
                    ],
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
