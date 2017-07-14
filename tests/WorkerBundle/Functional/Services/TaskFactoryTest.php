<?php

namespace Tests\WorkerBundle\Functional\Services;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Request\Task\CreateRequest;
use SimplyTestable\WorkerBundle\Services\TaskFactory;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;

class TaskFactoryTest extends BaseSimplyTestableTestCase
{
    /**
     * @var TaskFactory
     */
    private $taskFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskFactory = $this->container->get(TestTaskFactory::class);
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param string $taskTypeName
     * @param string $url
     * @param string $parameters
     */
    public function testCreate($taskTypeName, $url, $parameters)
    {
        $createRequest = $this->createCreateRequest(
            $taskTypeName,
            $url,
            $parameters
        );

        $task = $this->taskFactory->createFromRequest($createRequest);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals($taskTypeName, $task->getType()->getName());
        $this->assertEquals($url, $task->getUrl());
        $this->assertEquals($parameters, $task->getParameters());
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'foo' => [
                'taskTypeName' => 'HTML validation',
                'url' => 'http://example.com',
                'parameters' => '',
            ],
        ];
    }

    /**
     * @param string $taskTypeName
     * @param string $url
     * @param string $parameters
     *
     * @return CreateRequest
     */
    private function createCreateRequest($taskTypeName, $url, $parameters)
    {
        $taskTypeService = $this->container->get(TaskTypeService::class);
        $taskType = $taskTypeService->fetch($taskTypeName);

        return new CreateRequest($taskType, $url, $parameters);
    }
}
