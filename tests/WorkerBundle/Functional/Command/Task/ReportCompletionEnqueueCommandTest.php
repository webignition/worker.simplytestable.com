<?php

namespace Tests\WorkerBundle\Functional\Command\Task;

use GuzzleHttp\Psr7\Response;
use SimplyTestable\WorkerBundle\Command\Task\ReportCompletionEnqueueCommand;
use SimplyTestable\WorkerBundle\Resque\Job\TaskReportCompletionJob;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use Tests\WorkerBundle\Services\TestHttpClientService;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService;
use SimplyTestable\WorkerBundle\Services\TaskService;
use Symfony\Component\Console\Output\NullOutput;
use Tests\WorkerBundle\Functional\AbstractBaseTestCase;
use Tests\WorkerBundle\Factory\HtmlValidatorFixtureFactory;
use Tests\WorkerBundle\Factory\TestTaskFactory;
use Symfony\Component\Console\Input\ArrayInput;

class ReportCompletionEnqueueCommandTest extends AbstractBaseTestCase
{
    /**
     * @var ReportCompletionEnqueueCommand
     */
    private $command;

    /**
     * @var TestTaskFactory
     */
    private $testTaskFactory;

    /**
     * @var TestHttpClientService
     */
    private $httpClientService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = self::$container->get(ReportCompletionEnqueueCommand::class);
        $this->testTaskFactory = new TestTaskFactory(self::$container);
        $this->httpClientService = self::$container->get(HttpClientService::class);
    }

    public function testRunWithEmptyQueue()
    {
        $this->httpClientService->appendFixtures([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], '<!doctype html>'),
        ]);

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([]));
        self::$container->get(TaskService::class)->perform($task);

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
        $this->httpClientService->appendFixtures([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], '<!doctype html>'),
        ]);

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $task = $this->testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([]));
        self::$container->get(TaskService::class)->perform($task);

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

        $this->assertEquals(0, $this->httpClientService->getMockHandler()->count());
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
