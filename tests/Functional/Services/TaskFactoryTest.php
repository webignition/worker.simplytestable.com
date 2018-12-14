<?php

namespace App\Tests\Functional\Services;

use App\Entity\Task\Task;
use App\Model\Task\Parameters;
use App\Model\Task\TypeInterface;
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
        $taskTypeService = self::$container->get(TaskTypeService::class);
        $taskType = $taskTypeService->get($taskTypeName);

        $createRequest = new CreateRequest($url, $taskType, $parameters);

        $task = $this->taskFactory->createFromRequest($createRequest);

        $parametersArray = json_decode($parameters, true) ?? [];

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals($taskTypeName, $task->getType());
        $this->assertEquals($url, $task->getUrl());
        $this->assertEquals(new Parameters($parametersArray, $url), $task->getParametersObject());
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'empty parameters' => [
                'taskType' => TypeInterface::TYPE_HTML_VALIDATION,
                'url' => 'http://example.com',
                'parameters' => '',
            ],
            'non-empty parameters' => [
                'taskType' => TypeInterface::TYPE_HTML_VALIDATION,
                'url' => 'http://example.com',
                'parameters' => json_encode([
                    'foo' => 'bar',
                ])
            ],
        ];
    }
}
