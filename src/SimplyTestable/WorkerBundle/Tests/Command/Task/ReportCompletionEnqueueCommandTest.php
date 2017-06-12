<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task;

use SimplyTestable\WorkerBundle\Command\Task\ReportCompletionEnqueueCommand;
use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;
use SimplyTestable\WorkerBundle\Tests\Factory\HtmlValidatorFixtureFactory;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;

class ReportCompletionEnqueueCommandTest extends ConsoleCommandBaseTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAdditionalCommands()
    {
        return array(
            new ReportCompletionEnqueueCommand()
        );
    }

    public function testExecuteWithEmptyQueue()
    {
        $this->removeAllTasks();
        $this->setHttpFixtures([
            "HTTP/1.1 200 OK\nContent-type:text/html;\n\n<!doctype html>",
        ]);

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([]));
        $this->getTaskService()->perform($task);

        $this->assertTrue($this->clearRedis());

        $this->assertEquals(0, $this->executeCommand('simplytestable:task:reportcompletion:enqueue'));

        $this->assertTrue($this->getResqueQueueService()->contains(
            'task-report-completion',
            [
                'id' => $task->getId()
            ]
        ));
    }

    public function testExecuteWithNonEmptyQueue()
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
            $this->getResqueJobFactoryService()->create(
                'task-report-completion',
                ['id' => $task->getId()]
            )
        );

        $this->assertEquals(0, $this->executeCommand('simplytestable:task:reportcompletion:enqueue'));

        $this->assertTrue($this->getResqueQueueService()->contains(
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
