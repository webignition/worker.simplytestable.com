<?php

namespace SimplyTestable\WorkerBundle\Tests\Services\TaskDriver;

use Mockery\MockInterface;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CreateRequestCollectionFactory;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CreateRequestFactory;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use Symfony\Component\HttpFoundation\Request;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;

class CreateRequestCollectionFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param Request $request
     * @param int $expectedCollectionCount
     */
    public function testCreate(Request $request, $expectedCollectionCount)
    {
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

        $createRequestFactory = new CreateRequestFactory($request, $taskTypeService);
        $createRequestCollectionFactory = new CreateRequestCollectionFactory($request, $createRequestFactory);
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