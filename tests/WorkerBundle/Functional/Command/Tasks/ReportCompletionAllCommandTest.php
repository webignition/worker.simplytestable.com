<?php

namespace Tests\WorkerBundle\Functional\Command\Tasks;

use SimplyTestable\WorkerBundle\Command\Tasks\ReportCompletionCommand;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;
use Tests\WorkerBundle\Factory\HtmlValidatorFixtureFactory;
use Tests\WorkerBundle\Factory\TaskFactory;
use Symfony\Component\Console\Input\ArrayInput;

class ReportCompletionCommandTest extends BaseSimplyTestableTestCase
{
    public function testGetAsService()
    {
        $this->assertInstanceOf(
            ReportCompletionCommand::class,
            $this->container->get('simplytestable.command.tasks.reportcompletion')
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

        $command = $this->createReportCompletionCommand();

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
     * @return ReportCompletionCommand
     */
    private function createReportCompletionCommand()
    {
        return new ReportCompletionCommand(
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
