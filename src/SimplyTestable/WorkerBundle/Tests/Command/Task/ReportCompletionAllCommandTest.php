<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Task;

use SimplyTestable\WorkerBundle\Command\Task\ReportCompletionAllCommand;
use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;
use SimplyTestable\WorkerBundle\Tests\Factory\HtmlValidatorFixtureFactory;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;

class ReportCompletionAllCommandTest extends ConsoleCommandBaseTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAdditionalCommands()
    {
        return array(
            new ReportCompletionAllCommand()
        );
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param array $arguments
     * @param bool $expectedEntitiesAreRemoved
     */
    public function testReportCompletionAll($arguments, $expectedEntitiesAreRemoved)
    {
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

        $this->assertEquals(
            0,
            $this->executeCommand('simplytestable:task:reportcompletion:all', $arguments)
        );

        if ($expectedEntitiesAreRemoved) {
            $this->assertNull($task->getOutput()->getId());
            $this->assertNull($task->getId());
        } else {
            $this->assertNotNull($task->getOutput()->getId());
            $this->assertNotNull($task->getId());
        }
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'default' => [
                'arguments' => [],
                'expectedEntitiesAreRemoved' => true,
            ],
            'dry-run' => [
                'arguments' => [
                    '--dry-run' => true,
                ],
                'expectedEntitiesAreRemoved' => false,
            ],
        ];
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
