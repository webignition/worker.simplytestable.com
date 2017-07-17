<?php

namespace Tests\WorkerBundle\Functional\Command\Tasks;

use SimplyTestable\WorkerBundle\Command\Tasks\RequestCommand;
use SimplyTestable\WorkerBundle\Services\Resque\QueueService;
use SimplyTestable\WorkerBundle\Services\TasksService;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Symfony\Component\Console\Output\NullOutput;
use Tests\WorkerBundle\Factory\ConnectExceptionFactory;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;

class RequestCommandTest extends BaseSimplyTestableTestCase
{
    /**
     * @var RequestCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = $this->container->get(RequestCommand::class);
    }

    public function testMaintenanceMode()
    {
        $this->container->get(WorkerService::class)->setReadOnly();
        $this->clearRedis();

        $returnCode = $this->command->run(new ArrayInput([]), new NullOutput());

        $this->assertEquals(
            RequestCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );

        $this->assertFalse($this->container->get(QueueService::class)->isEmpty('tasks-request'));
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param bool $tasksServiceRequestReturnValue
     * @param int $expectedCommandReturnCode
     */
    public function testExecute($tasksServiceRequestReturnValue, $expectedCommandReturnCode, $expectedQueueIsEmpty)
    {
        $this->clearRedis();

        $tasksService = $this->container->get(TasksService::class);
        $tasksService
            ->setRequestResult($tasksServiceRequestReturnValue);

        $returnCode = $this->command->run(new ArrayInput([]), new NullOutput());

        $this->assertEquals(
            $expectedCommandReturnCode,
            $returnCode
        );

        $this->assertEquals(
            $expectedQueueIsEmpty,
            $this->container->get(QueueService::class)->isEmpty('tasks-request')
        );
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'success' => [
                'tasksServiceRequestReturnValue' => true,
                'expectedCommandReturnCode' => RequestCommand::RETURN_CODE_OK,
                'expectedQueueIsEmpty' => true,
            ],
            'outside threshold' => [
                'tasksServiceRequestReturnValue' => false,
                'expectedCommandReturnCode' => RequestCommand::RETURN_CODE_TASK_WORKLOAD_EXCEEDS_REQUEST_THRESHOLD,
                'expectedQueueIsEmpty' => false,
            ],
        ];
    }

    /**
     * @dataProvider executeRequestFailureDataProvider
     *
     * @param array $responseFixtures
     * @param int $expectedCommandReturnCode
     */
    public function testExecuteRequestFailure($responseFixtures, $expectedCommandReturnCode)
    {
        $this->setHttpFixtures($responseFixtures);
        $this->clearRedis();

        $returnCode = $this->command->run(new ArrayInput([]), new NullOutput());

        $this->assertEquals(
            $expectedCommandReturnCode,
            $returnCode
        );

        $this->assertFalse($this->container->get(QueueService::class)->isEmpty('tasks-request'));
    }

    /**
     * @return array
     */
    public function executeRequestFailureDataProvider()
    {
        return [
            'http 404' => [
                'responseFixtures' => [
                    'HTTP/1.1 404'
                ],
                'expectedCommandReturnCode' => RequestCommand::RETURN_CODE_FAILED,
            ],
            'curl 28' => [
                'responseFixtures' => [
                    ConnectExceptionFactory::create('CURL/28 Operation timed out.'),
                ],
                'expectedCommandReturnCode' => RequestCommand::RETURN_CODE_FAILED,
            ],
        ];
    }
}
