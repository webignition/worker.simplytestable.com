<?php

namespace Tests\WorkerBundle\Functional\Command\Task;

use SimplyTestable\WorkerBundle\Command\Task\ReportCompletionCommand;
use SimplyTestable\WorkerBundle\Services\TaskService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Symfony\Component\Console\Output\NullOutput;
use Tests\WorkerBundle\Factory\ConnectExceptionFactory;
use Tests\WorkerBundle\Factory\HtmlValidatorFixtureFactory;
use Tests\WorkerBundle\Factory\TestTaskFactory;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;

class ReportCompletionCommandTest extends BaseSimplyTestableTestCase
{
    /**
     * @var ReportCompletionCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = $this->container->get(ReportCompletionCommand::class);
    }

    public function testRunInMaintenanceReadOnlyMode()
    {
        $this->container->get(WorkerService::class)->setReadOnly();

        $returnCode = $this->command->execute(new ArrayInput([]), new NullOutput());

        $this->assertEquals(
            ReportCompletionCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }

    public function testRunForInvalidTask()
    {
         $returnCode = $this->command->run(new ArrayInput([
            'id' => -1
         ]), new NullOutput());

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
            "HTTP/1.1 200 OK\nContent-type:text/html;",
            "HTTP/1.1 200 OK\nContent-type:text/html;\n\n<!doctype html>",
        ], $responseFixtures));

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->getTestTaskFactory()->create(TestTaskFactory::createTaskValuesFromDefaults([
            'url' => 'http://example.com/',
            'type' => 'html validation',
        ]));
        $this->assertNotNull($task->getId());

        $this->container->get(TaskService::class)->perform($task);
        $this->assertNotNull($task->getOutput()->getId());

        $returnCode = $this->command->run(new ArrayInput([
            'id' => $task->getId()
        ]), new NullOutput());

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
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
