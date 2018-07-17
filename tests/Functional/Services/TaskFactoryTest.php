<?php

namespace App\Tests\Functional\Services;

use App\Entity\Task\Task;
use App\Request\Task\CreateRequest;
use App\Services\TaskFactory;
use App\Services\TaskTypeService;
use App\Tests\Functional\AbstractBaseTestCase;

class TaskFactoryTest extends AbstractBaseTestCase
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

        $this->taskFactory = self::$container->get(TaskFactory::class);
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
            'default' => [
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
        $taskTypeService = self::$container->get(TaskTypeService::class);
        $taskType = $taskTypeService->fetch($taskTypeName);

        return new CreateRequest($taskType, $url, $parameters);
    }
}
