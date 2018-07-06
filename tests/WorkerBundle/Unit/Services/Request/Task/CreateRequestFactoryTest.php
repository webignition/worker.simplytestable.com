<?php

namespace Tests\WorkerBundle\Unit\Request\Task;

use Mockery\MockInterface;
use SimplyTestable\WorkerBundle\Services\Request\Factory\Task\CreateRequestFactory;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use Symfony\Component\HttpFoundation\Request;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;
use Symfony\Component\HttpFoundation\RequestStack;

class CreateRequestFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param Request $request
     * @param TaskTypeService $taskTypeService
     * @param TaskType$expectedTaskType
     * @param string $expectedUrl
     * @param string $expectedParameters
     */
    public function testCreate(
        Request $request,
        TaskTypeService $taskTypeService,
        $expectedTaskType,
        $expectedUrl,
        $expectedParameters
    ) {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $createRequestFactory = new CreateRequestFactory($requestStack, $taskTypeService);
        $createRequest = $createRequestFactory->create();

        $this->assertEquals($expectedTaskType, $createRequest->getTaskType());
        $this->assertEquals($expectedUrl, $createRequest->getUrl());
        $this->assertEquals($expectedParameters, $createRequest->getParameters());
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        $taskType = new TaskType();

        return [
            'empty task type' => [
                'request' => new Request(),
                'taskTypeService' => \Mockery::mock(TaskTypeService::class),
                'expectedTaskType' => null,
                'expectedUrl' => '',
                'expectedParameters' => '',
            ],
            'invalid task type' => [
                'request' => new Request([], [
                    'type' => 'foo',
                ]),
                'taskTypeService' => $this->createTaskTypeService(
                    'foo',
                    null
                ),
                'expectedTaskType' => null,
                'expectedUrl' => '',
                'expectedParameters' => '',
            ],
            'valid task type' => [
                'request' => new Request([], [
                    'type' => 'foo',
                ]),
                'taskTypeService' => $this->createTaskTypeService(
                    'foo',
                    $taskType
                ),
                'expectedTaskType' => $taskType,
                'expectedUrl' => '',
                'expectedParameters' => '',
            ],
        ];
    }

    /**
     * @param string $taskTypeName
     * @param TaskType|null $fetchReturnValue
     * @return MockInterface|TaskTypeService
     */
    private function createTaskTypeService($taskTypeName, $fetchReturnValue)
    {
        $taskTypeService = \Mockery::mock(TaskTypeService::class);

        $taskTypeService
            ->shouldReceive('fetch')
            ->with($taskTypeName)
            ->andReturn($fetchReturnValue);

        return $taskTypeService;
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
