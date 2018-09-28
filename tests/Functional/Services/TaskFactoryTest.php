<?php

namespace App\Tests\Functional\Services;

use App\Entity\Task\Task;
use App\Request\Task\CreateRequest;
use App\Services\TaskFactory;
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
     * @param string $taskType
     * @param string $url
     * @param string $parameters
     */
    public function testCreate($taskType, $url, $parameters)
    {
        $createRequest = new CreateRequest($taskType, $url, $parameters);

        $task = $this->taskFactory->createFromRequest($createRequest);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals($taskType, $task->getType());
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
                'taskType' => 'HTML validation',
                'url' => 'http://example.com',
                'parameters' => '',
            ],
        ];
    }
}
