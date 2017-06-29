<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Command\Task;

use SimplyTestable\WorkerBundle\Command\Task\ReportCompletionAllCommand;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Tests\Functional\BaseSimplyTestableTestCase;
use SimplyTestable\WorkerBundle\Tests\Factory\HtmlValidatorFixtureFactory;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;
use Symfony\Component\Console\Input\ArrayInput;

class ReportCompletionAllCommandTest extends BaseSimplyTestableTestCase
{
    public function testGetAsService()
    {
        $this->assertInstanceOf(
            ReportCompletionAllCommand::class,
            $this->container->get('simplytestable.command.task.reportcompletionall')
        );
    }

    public function testRun()
    {
        $this->removeAllTasks();

        $this->setHttpFixtures([
            "HTTP/1.1 200 OK\nContent-type:text/html;\n\n<!doctype html>",
            "HTTP/1.1 200 OK",
        ]);

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([
            'url' => 'http://example.com/',
            'type' => 'html validation',
        ]));
        $this->assertNotNull($task->getId());

        $this->getTaskService()->perform($task);
        $this->assertNotNull($task->getOutput()->getId());

        $command = $this->createReportCompletionAllCommand();

        $returnCode = $command->run(
            new ArrayInput([]),
            new StringOutput()
        );

        $this->assertEquals(
            0,
            $returnCode
        );

        $this->assertNull($task->getOutput()->getId());
        $this->assertNull($task->getId());
    }

    /**
     * @return ReportCompletionAllCommand
     */
    private function createReportCompletionAllCommand()
    {
        return new ReportCompletionAllCommand(
            $this->container->get('logger'),
            $this->container->get('simplytestable.services.taskservice'),
            $this->container->get('simplytestable.services.workerservice')
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
