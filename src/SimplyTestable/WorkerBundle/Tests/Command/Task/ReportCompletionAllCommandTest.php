<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task;

use SimplyTestable\WorkerBundle\Command\Task\ReportCompletionAllCommand;
use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;
use SimplyTestable\WorkerBundle\Entity\TimePeriod;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;

class ReportCompletionAllCommandTest extends ConsoleCommandBaseTestCase
{
    protected function getAdditionalCommands()
    {
        return array(
            new ReportCompletionAllCommand()
        );
    }

    public function testReportCompletionAll()
    {
        $this->setHttpFixtures($this->getHttpFixtures($this->getFixturesDataPath(__FUNCTION__ . '/HttpResponses')));
        $this->removeAllTasks();

        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([
            'url' => 'http://example.com/',
            'type' => 'html validation',
        ]));

        $taskTimePeriod = new TimePeriod();
        $taskTimePeriod->setStartDateTime(new \DateTime('1970-01-01'));
        $taskTimePeriod->setEndDateTime(new \DateTime('1970-01-02'));

        $task->setTimePeriod($taskTimePeriod);

        $this->createCompletedTaskOutputForTask($task);

        $this->assertEquals(0, $this->executeCommand('simplytestable:task:reportcompletion:all'));
    }
}
