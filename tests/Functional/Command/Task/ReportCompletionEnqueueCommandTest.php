<?php

namespace App\Tests\Functional\Command\Task;

use App\Services\TaskPerformanceService;
use App\Tests\TestServices\TaskFactory;
use GuzzleHttp\Psr7\Response;
use App\Command\Task\ReportCompletionEnqueueCommand;
use App\Resque\Job\TaskReportCompletionJob;
use App\Tests\Services\HttpMockHandler;
use App\Services\Resque\QueueService;
use Symfony\Component\Console\Output\NullOutput;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Factory\HtmlValidatorFixtureFactory;
use Symfony\Component\Console\Input\ArrayInput;

class ReportCompletionEnqueueCommandTest extends AbstractBaseTestCase
{
    /**
     * @var ReportCompletionEnqueueCommand
     */
    private $command;

    /**
     * @var TaskFactory
     */
    private $testTaskFactory;

    /**
     * @var HttpMockHandler
     */
    private $httpMockHandler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = self::$container->get(ReportCompletionEnqueueCommand::class);
        $this->testTaskFactory = self::$container->get(TaskFactory::class);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
    }

    public function testRunWithEmptyQueue()
    {
        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], '<!doctype html>'),
        ]);

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->testTaskFactory->create(TaskFactory::createTaskValuesFromDefaults([]));
        self::$container->get(TaskPerformanceService::class)->perform($task);

        $this->assertTrue($this->clearRedis());

        $returnCode = $this->command->execute(new ArrayInput([]), new NullOutput());

        $this->assertEquals(0, $returnCode);

        $resqueQueueService = self::$container->get(QueueService::class);

        $this->assertTrue($resqueQueueService->contains(
            'task-report-completion',
            [
                'id' => $task->getId()
            ]
        ));
    }

    /**
     * @throws \CredisException
     * @throws \Exception
     */
    public function testRunWithNonEmptyQueue()
    {
        $this->httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], '<!doctype html>'),
        ]);

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->testTaskFactory->create(TaskFactory::createTaskValuesFromDefaults([]));
        self::$container->get(TaskPerformanceService::class)->perform($task);

        $this->assertTrue($this->clearRedis());

        $resqueQueueService = self::$container->get(QueueService::class);
        $resqueQueueService->enqueue(new TaskReportCompletionJob(['id' => $task->getId()]));

        $returnCode = $this->command->execute(new ArrayInput([]), new NullOutput());

        $this->assertEquals(0, $returnCode);

        $this->assertTrue($resqueQueueService->contains(
            'task-report-completion',
            [
                'id' => $task->getId()
            ]
        ));
    }

    protected function assertPostConditions()
    {
        parent::assertPostConditions();

        $this->assertEquals(0, $this->httpMockHandler->count());
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
