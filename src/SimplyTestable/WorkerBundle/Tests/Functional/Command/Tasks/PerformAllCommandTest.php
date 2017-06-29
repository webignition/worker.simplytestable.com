<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Command\Tasks;

use Mockery\MockInterface;
use SimplyTestable\WorkerBundle\Command\Tasks\PerformCommand;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;
use Symfony\Component\Console\Input\ArrayInput;

class PerformCommandTest extends BaseSimplyTestableTestCase
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

    public function testGetAsService()
    {
        $this->assertInstanceOf(
            PerformCommand::class,
            $this->container->get('simplytestable.command.tasks.perform')
        );
    }

    public function testRun()
    {
        $this->removeAllTasks();

        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([]));

        /* @var TaskService|MockInterface $taskService */
        $taskService = $this->container->get('simplytestable.services.taskservice');
        $taskService
            ->shouldReceive('perform')
            ->with($task)
            ->andReturn(0);

        $command = $this->createPerformCommand();

        $returnCode = $command->run(
            new ArrayInput([]),
            new StringOutput()
        );

        $this->assertEquals(0, $returnCode);
    }

    /**
     * @return PerformCommand
     */
    private function createPerformCommand()
    {
        return new PerformCommand(
            $this->container->get('logger'),
            $this->container->get('simplytestable.services.taskservice'),
            $this->container->get('simplytestable.services.workerservice'),
            $this->container->get('simplytestable.services.resque.queueservice'),
            $this->container->get('simplytestable.services.resque.jobfactory')
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
