<?php

namespace Tests\AppBundle\Functional\Command\Task;

use GuzzleHttp\Psr7\Response;
use App\Command\Task\PerformCommand;
use App\Services\Resque\QueueService;
use App\Services\TaskTypeService;
use App\Services\WorkerService;
use Symfony\Component\Console\Output\NullOutput;
use Tests\AppBundle\Factory\CssValidatorFixtureFactory;
use Tests\AppBundle\Factory\HtmlValidatorFixtureFactory;
use Tests\AppBundle\Factory\TestTaskFactory;
use Tests\AppBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Tests\AppBundle\Services\HttpMockHandler;

/**
 * @group Command/Task/PerformCommand
 */
class PerformCommandTest extends AbstractBaseTestCase
{
    /**
     * @var PerformCommand
     */
    private $command;

    protected function setUp()
    {
        parent::setUp();

        $this->command = self::$container->get(PerformCommand::class);
    }

    /**
     * @throws \Exception
     */
    public function testRunInMaintenanceReadOnlyMode()
    {
        self::$container->get(WorkerService::class)->setReadOnly();
        $this->clearRedis();

        $testTaskFactory = new TestTaskFactory(self::$container);
        $task = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([]));

        $returnCode = $this->command->run(
            new ArrayInput([
                'id' => $task->getId(),
            ]),
            new NullOutput()
        );

        $this->assertEquals(PerformCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);
        $this->assertTrue(self::$container->get(QueueService::class)->contains(
            'task-perform',
            [
                'id' => $task->getId()
            ]
        ));
    }

    /**
     * @throws \Exception
     */
    public function testTaskServiceRaisesException()
    {
        $testTaskFactory = new TestTaskFactory(self::$container);
        $httpMockHandler = self::$container->get(HttpMockHandler::class);

        $httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], '<!doctype html>'),
        ]);

        $task = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'type' => TaskTypeService::CSS_VALIDATION_NAME,
        ]));

        CssValidatorFixtureFactory::set(CssValidatorFixtureFactory::load('invalid-validator-output'));

        $returnCode = $this->command->run(
            new ArrayInput([
                'id' => $task->getId(),
            ]),
            new NullOutput()
        );

        $this->assertEquals(PerformCommand::RETURN_CODE_TASK_SERVICE_RAISED_EXCEPTION, $returnCode);
    }

    /**
     * @throws \Exception
     */
    public function testPerformSuccess()
    {
        $httpMockHandler = self::$container->get(HttpMockHandler::class);
        $resqueQueueService = self::$container->get(QueueService::class);

        $httpMockHandler->appendFixtures([
            new Response(200, ['content-type' => 'text/html']),
            new Response(200, ['content-type' => 'text/html'], '<!doctype html>'),
        ]);

        HtmlValidatorFixtureFactory::set(HtmlValidatorFixtureFactory::load('0-errors'));

        $testTaskFactory = new TestTaskFactory(self::$container);

        $task = $testTaskFactory->create(TestTaskFactory::createTaskValuesFromDefaults([
            'url' => 'http://example.com/',
            'type' => 'html validation',
        ]));

        $returnCode = $this->command->run(
            new ArrayInput([
                'id' => $task->getId(),
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
                    $data[$key] = $task->getId();
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
