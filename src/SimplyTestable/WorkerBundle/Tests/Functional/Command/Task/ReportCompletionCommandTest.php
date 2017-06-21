<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Command\Task;

use SimplyTestable\WorkerBundle\Command\Task\ReportCompletionCommand;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Tests\Factory\ConnectExceptionFactory;
use SimplyTestable\WorkerBundle\Tests\Factory\HtmlValidatorFixtureFactory;
use SimplyTestable\WorkerBundle\Tests\Factory\TaskFactory;
use SimplyTestable\WorkerBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;

class ReportCompletionCommandTest extends BaseSimplyTestableTestCase
{
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

    public function testRunInMaintenanceReadOnlyMode()
    {
        $this->getWorkerService()->setReadOnly();

        $command = $this->createReportCompletionCommand();

        $returnCode = $command->execute(new ArrayInput([]), new StringOutput());

        $this->assertEquals(
            ReportCompletionCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }

    public function testRunForInvalidTask()
    {
        $command = $this->createReportCompletionCommand();

        $returnCode = $command->run(new ArrayInput([
            'id' => -1
        ]), new StringOutput());

        $this->assertEquals(
            ReportCompletionCommand::RETURN_CODE_TASK_DOES_NOT_EXIST,
            $returnCode
        );
    }

    /**
     * @dataProvider runDataProvider
     *
     * @param array $responseFixtures
     * @param int $expectedCommandReturnCode
     * @param bool $expectedTaskIsDeleted
     */
    public function testRun($responseFixtures, $expectedCommandReturnCode, $expectedTaskIsDeleted)
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

        $command = $this->createReportCompletionCommand();

        $returnCode = $command->run(new ArrayInput([
            'id' => $task->getId()
        ]), new StringOutput());

        $this->assertEquals(
            $expectedCommandReturnCode,
            $returnCode
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
    public function runDataProvider()
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
     * @return ReportCompletionCommand
     */
    private function createReportCompletionCommand()
    {
        return new ReportCompletionCommand(
            $this->container->get('logger'),
            $this->container->get('simplytestable.services.taskservice'),
            $this->container->get('simplytestable.services.workerservice'),
            $this->container->get('doctrine.orm.entity_manager')
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
