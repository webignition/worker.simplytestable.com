<?php

namespace Tests\WorkerBundle\Functional\Command\Task;

use SimplyTestable\WorkerBundle\Command\Task\ReportCompletionEnqueueCommand;
use webignition\ResqueJobFactory\ResqueJobFactory;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService;
use SimplyTestable\WorkerBundle\Services\TaskService;
use Symfony\Component\Console\Output\NullOutput;
use Tests\WorkerBundle\Functional\AbstractBaseTestCase;
use Tests\WorkerBundle\Factory\HtmlValidatorFixtureFactory;
use Tests\WorkerBundle\Factory\TestTaskFactory;
use Symfony\Component\Console\Input\ArrayInput;

class ReportCompletionEnqueueCommandTest extends AbstractBaseTestCase
{
    /**
     * @var ReportCompletionEnqueueCommand
     */
    private $command;

    /**
     * @var TestTaskFactory
     */
    private $testTaskFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = $this->container->get(ReportCompletionEnqueueCommand::class);
        $this->testTaskFactory = new TestTaskFactory($this->container);
    }

    public function testRunWithEmptyQueue()
    {
        $this->setHttpFixtures([
            "HTTP/1.1 200 OK\nContent-type:text/html;",
            "HTTP/1.1 200 OK\nContent-type:text/html;\n\n<!doctype html>",
        ]);

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([]));
        $this->container->get(TaskService::class)->perform($task);

        $this->assertTrue($this->clearRedis());

        $returnCode = $this->command->execute(new ArrayInput([]), new NullOutput());

        $this->assertEquals(0, $returnCode);

        $resqueQueueService = $this->container->get(QueueService::class);

        $this->assertTrue($resqueQueueService->contains(
            'task-report-completion',
            [
                'id' => $task->getId()
            ]
        ));
    }

    /**
     * @throws \CredisException
     * @throws \Exception
     */
    public function testRunWithNonEmptyQueue()
    {
        $this->setHttpFixtures([
            "HTTP/1.1 200 OK\nContent-type:text/html;",
            "HTTP/1.1 200 OK\nContent-type:text/html;\n\n<!doctype html>",
        ]);

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([]));
        $this->container->get(TaskService::class)->perform($task);

        $this->assertTrue($this->clearRedis());

        $resqueQueueService = $this->container->get(QueueService::class);
        $resqueJobFactory = $this->container->get(ResqueJobFactory::class);

        $resqueQueueService->enqueue(
            $resqueJobFactory->create(
                'task-report-completion',
                ['id' => $task->getId()]
            )
        );

        $returnCode = $this->command->execute(new ArrayInput([]), new NullOutput());

        $this->assertEquals(0, $returnCode);

        $this->assertTrue($resqueQueueService->contains(
            'task-report-completion',
            [
                'id' => $task->getId()
            ]
        ));
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
