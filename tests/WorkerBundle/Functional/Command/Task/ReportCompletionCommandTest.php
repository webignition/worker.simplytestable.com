<?php

namespace Tests\WorkerBundle\Functional\Command\Task;

use SimplyTestable\WorkerBundle\Command\Task\ReportCompletionCommand;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use Tests\WorkerBundle\Factory\ConnectExceptionFactory;
use Tests\WorkerBundle\Factory\HtmlValidatorFixtureFactory;
use Tests\WorkerBundle\Factory\TaskFactory;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;
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

    public function testGetAsService()
    {
        $this->assertInstanceOf(
            ReportCompletionCommand::class,
            $this->container->get('simplytestable.command.task.reportcompletion')
        );
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
     */
    public function testRun($responseFixtures, $expectedCommandReturnCode)
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
            ],
            'http 404' => [
                'responseFixtures' => [
                    'HTTP/1.1 404',
                ],
                'expectedCommandReturnCode' => 404,
            ],
            'http 500' => [
                'responseFixtures' => [
                    'HTTP/1.1 500',
                ],
                'expectedCommandReturnCode' => 500,
            ],
            'curl 28' => [
                'responseFixtures' => [
                    ConnectExceptionFactory::create('CURL/28 Operation timed out.'),
                ],
                'expectedCommandReturnCode' => 28,
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
