<?php

namespace App\Tests\Functional\Command\Task;

use App\Entity\Task\Task;
use App\Tests\Services\TestTaskFactory;
use GuzzleHttp\Psr7\Response;
use App\Command\Task\PerformCommand;
use App\Services\Resque\QueueService;
use App\Services\WorkerService;
use Symfony\Component\Console\Output\NullOutput;
use App\Tests\Factory\HtmlValidatorFixtureFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use App\Tests\Services\HttpMockHandler;

/**
 * @group Command/Task/PerformCommand
 */
class PerformCommandTest extends AbstractBaseTestCase
{
    /**
     * @var PerformCommand
     */
    private $command;

    /**
     * @var Task
     */
    private $task;

    protected function setUp()
    {
        parent::setUp();

        $this->command = self::$container->get(PerformCommand::class);
        $testTaskFactory = self::$container->get(TestTaskFactory::class);

        $this->task = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'url' => 'http://example.com/',
            'type' => 'html validation',
        ]));

        $httpMockHandler = self::$container->get(HttpMockHandler::class);

        $httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], '<!doctype html>'),
        ]);
    }

    /**
     * @throws \Exception
     */
    public function testRunInMaintenanceReadOnlyMode()
    {
        self::$container->get(WorkerService::class)->setReadOnly();
        $this->clearRedis();

        $returnCode = $this->command->run(
            new ArrayInput([
                'id' => $this->task->getId(),
            ]),
            new NullOutput()
        );

        $this->assertEquals(PerformCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);
        $this->assertTrue(self::$container->get(QueueService::class)->contains(
            'task-perform',
            [
                'id' => $this->task->getId()
            ]
        ));
    }

    /**
     * @throws \Exception
     */
    public function testRunSuccess()
    {
        $resqueQueueService = self::$container->get(QueueService::class);

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $returnCode = $this->command->run(
            new ArrayInput([
                'id' => $this->task->getId(),
            ]),
            new NullOutput()
        );

        $this->assertEquals(0, $returnCode);

        $expectedResqueJobs = [
            'tasks-request' => [],
            'task-report-completion' => [
                'id' => '{{ taskId }}',
            ],
        ];

        foreach ($expectedResqueJobs as $queueName => $data) {
            foreach ($data as $key => $value) {
                if ($value == '{{ taskId }}') {
                    $data[$key] = $this->task->getId();
                }
            }

            $this->assertFalse($resqueQueueService->isEmpty($queueName));
            $this->assertTrue($resqueQueueService->contains($queueName, $data));
        }
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
