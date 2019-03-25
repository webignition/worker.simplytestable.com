<?php

namespace App\Tests\Unit\Command\Tasks;

use App\Command\Tasks\RequestIfEmptyCommand;
use App\Resque\Job\TasksRequestJob;
use Mockery\MockInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use App\Tests\Factory\MockFactory;
use App\Services\Resque\QueueService as ResqueQueueService;

/**
 * @group Command/Tasks/RequestIfEmptyCommand
 */
class RequestIfEmptyCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider runDataProvider
     *
     * @param ResqueQueueService $resqueQueueService
     * @throws \Exception
     */
    public function testRun(ResqueQueueService $resqueQueueService)
    {
        $command = $this->createRequestIfEmptyCommand([ResqueQueueService::class => $resqueQueueService]);

        $returnCode = $command->run(new ArrayInput([]), new NullOutput());

        $this->assertEquals(
            0,
            $returnCode
        );
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        return [
            'tasks-request queue is empty' => [
                'resqueQueueService' => $this->createResqueQueueServiceWithEnqueueCall(),
            ],
            'tasks-request queue is not empty' => [
                'resqueQueueService' => $this->createResqueQueueService(false)
            ],
        ];
    }

    /**
     * @return MockInterface|ResqueQueueService
     */
    private function createResqueQueueServiceWithEnqueueCall()
    {
        $resqueQueueService = $this->createResqueQueueService(true);

        $resqueQueueService
            ->shouldReceive('enqueue')
            ->withArgs(function (TasksRequestJob $tasksRequestJob) {
                $this->assertInstanceOf(TasksRequestJob::class, $tasksRequestJob);
                return true;
            });

        return $resqueQueueService;
    }

    /**
     * @param bool $isEmpty
     *
     * @return MockInterface|ResqueQueueService
     */
    private function createResqueQueueService($isEmpty)
    {
        $resqueQueueService = MockFactory::createResqueQueueService();

        $resqueQueueService
            ->shouldReceive('isEmpty')
            ->with('tasks-request')
            ->andReturn($isEmpty);

        return $resqueQueueService;
    }

    /**
     * @param array $services
     *
     * @return RequestIfEmptyCommand
     */
    private function createRequestIfEmptyCommand($services = [])
    {
        if (!isset($services[ResqueQueueService::class])) {
            $services[ResqueQueueService::class] = MockFactory::createResqueQueueService();
        }

        return new RequestIfEmptyCommand($services[ResqueQueueService::class]);
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
