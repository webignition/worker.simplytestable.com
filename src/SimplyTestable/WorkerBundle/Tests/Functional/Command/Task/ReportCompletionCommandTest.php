<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Command\Task;

use SimplyTestable\WorkerBundle\Command\Task\ReportCompletionCommand;
use SimplyTestable\WorkerBundle\Tests\Functional\Command\ConsoleCommandBaseTestCase;
use SimplyTestable\WorkerBundle\Tests\Factory\ConnectExceptionFactory;
use SimplyTestable\WorkerBundle\Tests\Factory\HtmlValidatorFixtureFactory;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;

class ReportCompletionCommandTest extends ConsoleCommandBaseTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAdditionalCommands()
    {
        return array(
            new ReportCompletionCommand()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected static function getServicesToMock()
    {
        return [
            'simplytestable.services.workerservice',
            'simplytestable.services.taskservice',
        ];
    }

    public function testExecuteInMaintenanceReadOnlyMode()
    {
        $this->getWorkerService()->setReadOnly();

        $this->assertEquals(
            ReportCompletionCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $this->executeCommand(
                'simplytestable:task:reportcompletion',
                [
                    'id' => 1
                ]
            )
        );
    }

    public function testExecuteForInvalidTask()
    {
        $this->assertEquals(
            ReportCompletionCommand::RETURN_CODE_TASK_DOES_NOT_EXIST,
            $this->executeCommand(
                'simplytestable:task:reportcompletion',
                [
                    'id' => -1
                ]
            )
        );
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param array $responseFixtures
     * @param int $expectedCommandReturnCode
     * @param bool $expectedTaskIsDeleted
     */
    public function testExecute($responseFixtures, $expectedCommandReturnCode, $expectedTaskIsDeleted)
    {
        $this->setHttpFixtures(array_merge([
            "HTTP/1.1 200 OK\nContent-type:text/html;\n\n<!doctype html>",
        ], $responseFixtures));

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->getTaskFactory()->create(TaskFactory::createTaskValuesFromDefaults([
            'url' => 'http://example.com/',
            'type' => 'html validation',
        ]));
        $this->assertNotNull($task->getId());

        $this->getTaskService()->perform($task);
        $this->assertNotNull($task->getOutput()->getId());

        $this->assertEquals(
            $expectedCommandReturnCode,
            $this->executeCommand(
                'simplytestable:task:reportcompletion',
                [
                    'id' => $task->getId()
                ]
            )
        );

        if ($expectedTaskIsDeleted) {
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
            'http 200' => [
                'responseFixtures' => [
                    'HTTP/1.1 200',
                ],
                'expectedCommandReturnCode' => 0,
                'expectedTaskIsDeleted' => true,
            ],
            'http 404' => [
                'responseFixtures' => [
                    'HTTP/1.1 404',
                ],
                'expectedCommandReturnCode' => 404,
                'expectedTaskIsDeleted' => false,
            ],
            'http 500' => [
                'responseFixtures' => [
                    'HTTP/1.1 500',
                ],
                'expectedCommandReturnCode' => 500,
                'expectedTaskIsDeleted' => false,
            ],
            'curl 28' => [
                'responseFixtures' => [
                    ConnectExceptionFactory::create('CURL/28 Operation timed out.'),
                ],
                'expectedCommandReturnCode' => 28,
                'expectedTaskIsDeleted' => false,
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
