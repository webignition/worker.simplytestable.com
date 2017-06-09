<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task;

use Mockery\MockInterface;
use SimplyTestable\WorkerBundle\Command\Task\PerformAllCommand;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;

class PerformAllCommandTest extends ConsoleCommandBaseTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->clearRedis();
    }

    /**
     * {@inheritdoc}
     */
    protected function getAdditionalCommands()
    {
        return array(
            new PerformAllCommand()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected static function getServicesToMock()
    {
        return [
            'simplytestable.services.taskservice',
        ];
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param array $arguments
     */
    public function testExecute($arguments)
    {
        $this->removeAllTasks();

        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([]));

        /* @var TaskService|MockInterface $taskService */
        $taskService = $this->container->get('simplytestable.services.taskservice');
        $taskService
            ->shouldReceive('perform')
            ->with($task)
            ->andReturn(0);

        $response = $this->executeCommand('simplytestable:task:perform:all', $arguments);

        $this->assertEquals(0, $response);
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'default' => [
                'arguments' => [],
            ],
            'dry-run' => [
                'arguments' => [
                    '--dry-run' => true,
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->clearRedis();
        \Mockery::close();
    }
}
