<?php

namespace Tests\AppBundle\Functional\Command\Task;

use GuzzleHttp\Psr7\Response;
use AppBundle\Command\Task\ReportCompletionEnqueueCommand;
use AppBundle\Resque\Job\TaskReportCompletionJob;
use Tests\AppBundle\Services\HttpMockHandler;
use AppBundle\Services\Resque\QueueService;
use AppBundle\Services\TaskService;
use Symfony\Component\Console\Output\NullOutput;
use Tests\AppBundle\Functional\AbstractBaseTestCase;
use Tests\AppBundle\Factory\HtmlValidatorFixtureFactory;
use Tests\AppBundle\Factory\TestTaskFactory;
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
        $this->testTaskFactory = new TestTaskFactory(self::$container);
        $this->httpMockHandler = self::$container->get(HttpMockHandler::class);
    }

    public function testRunWithEmptyQueue()
    {
        $this->httpMockHandler->appendFixtures([
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
        $this->httpMockHandler->appendFixtures([
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