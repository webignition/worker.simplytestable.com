<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Command\Task;

use Mockery\MockInterface;
use SimplyTestable\WorkerBundle\Command\Task\PerformAllCommand;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;
use Symfony\Component\Console\Input\ArrayInput;

class PerformAllCommandTest extends BaseSimplyTestableTestCase
{
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
     * @dataProvider runDataProvider
     *
     * @param array $arguments
     */
    public function testRun($arguments)
    {
        $this->removeAllTasks();

        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([]));

        /* @var TaskService|MockInterface $taskService */
        $taskService = $this->container->get('simplytestable.services.taskservice');
        $taskService
            ->shouldReceive('perform')
            ->with($task)
            ->andReturn(0);

        $command = $this->createPerformAllCommand();

        $returnCode = $command->run(
            new ArrayInput($arguments),
            new StringOutput()
        );

        $this->assertEquals(0, $returnCode);
    }

    /**
     * @return array
     */
    public function runDataProvider()
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
     * @return PerformAllCommand
     */
    private function createPerformAllCommand()
    {
        return new PerformAllCommand(
            $this->container->get('logger'),
            $this->container->get('simplytestable.services.taskservice'),
            $this->container->get('simplytestable.services.workerservice'),
            $this->container->get('simplytestable.services.resque.queueservice'),
            $this->container->get('simplytestable.services.resque.jobfactoryservice')
        );
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
