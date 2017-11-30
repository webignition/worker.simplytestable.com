<?php

namespace Tests\WorkerBundle\Functional\Command\Tasks;

use SimplyTestable\WorkerBundle\Command\Tasks\PerformCommand;
use SimplyTestable\WorkerBundle\Services\TaskService;
use Symfony\Component\Console\Output\NullOutput;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;
use Tests\WorkerBundle\Factory\TestTaskFactory;
use Symfony\Component\Console\Input\ArrayInput;

class PerformCommandTest extends BaseSimplyTestableTestCase
{
    /**
     * @throws \Exception
     */
    public function testRun()
    {
        $this->removeAllTasks();

        $this->getTestTaskFactory()->create(TestTaskFactory::createTaskValuesFromDefaults([]));

        $taskService = $this->container->get(TaskService::class);
        $taskService
            ->setPerformResult(0);

        $command = $this->container->get(PerformCommand::class);

        $returnCode = $command->run(
            new ArrayInput([]),
            new NullOutput()
        );

        $this->assertEquals(0, $returnCode);
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
