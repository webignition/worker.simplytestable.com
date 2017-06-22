<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Command\Task;

use SimplyTestable\WorkerBundle\Command\Task\ReportCompletionEnqueueCommand;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\WorkerBundle\Tests\Factory\HtmlValidatorFixtureFactory;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;
use Symfony\Component\Console\Input\ArrayInput;

class ReportCompletionEnqueueCommandTest extends BaseSimplyTestableTestCase
{
    public function testRunWithEmptyQueue()
    {
        $this->removeAllTasks();
        $this->setHttpFixtures([
            "HTTP/1.1 200 OK\nContent-type:text/html;\n\n<!doctype html>",
        ]);

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([]));
        $this->getTaskService()->perform($task);

        $this->assertTrue($this->clearRedis());

        $command = $this->createReportCompletionEnqueueCommand();
        $returnCode = $command->execute(new ArrayInput([]), new StringOutput());

        $this->assertEquals(0, $returnCode);

        $this->assertTrue($this->getResqueQueueService()->contains(
            'task-report-completion',
            [
                'id' => $task->getId()
            ]
        ));
    }

    public function testRunWithNonEmptyQueue()
    {
        $this->removeAllTasks();
        $this->setHttpFixtures([
            "HTTP/1.1 200 OK\nContent-type:text/html;\n\n<!doctype html>",
        ]);

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([]));
        $this->getTaskService()->perform($task);

        $this->assertTrue($this->clearRedis());

        $this->getResqueQueueService()->enqueue(
            $this->getResqueJobFactory()->create(
                'task-report-completion',
                ['id' => $task->getId()]
            )
        );

        $command = $this->createReportCompletionEnqueueCommand();
        $returnCode = $command->execute(new ArrayInput([]), new StringOutput());

        $this->assertEquals(0, $returnCode);

        $this->assertTrue($this->getResqueQueueService()->contains(
            'task-report-completion',
            [
                'id' => $task->getId()
            ]
        ));
    }

    /**
     * @return ReportCompletionEnqueueCommand
     */
    private function createReportCompletionEnqueueCommand()
    {
        return new ReportCompletionEnqueueCommand(
            $this->container->get('logger'),
            $this->container->get('simplytestable.services.taskservice'),
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
