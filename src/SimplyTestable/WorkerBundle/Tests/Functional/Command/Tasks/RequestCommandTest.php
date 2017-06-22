<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Command\Tasks;

use SimplyTestable\WorkerBundle\Command\Tasks\RequestCommand;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Tests\Factory\ConnectExceptionFactory;
use SimplyTestable\WorkerBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;

class RequestCommandTest extends BaseSimplyTestableTestCase
{
    /**
     * {@inheritdoc}
     */
    protected static function getServicesToMock()
    {
        return [
            'simplytestable.services.tasksservice',
        ];
    }

    public function testMaintenanceMode()
    {
        $this->getWorkerService()->setReadOnly();
        $this->clearRedis();

        $command = $this->createRequestCommand();
        $returnCode = $command->run(new ArrayInput([]), new StringOutput());

        $this->assertEquals(
            RequestCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );

        $this->assertFalse($this->getResqueQueueService()->isEmpty('tasks-request'));
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

        $this->container->get('simplytestable.services.tasksservice')
            ->shouldReceive('request')
            ->andReturn($tasksServiceRequestReturnValue);

        $command = $this->createRequestCommand();
        $returnCode = $command->run(new ArrayInput([]), new StringOutput());

        $this->assertEquals(
            $expectedCommandReturnCode,
            $returnCode
        );

        $this->assertEquals($expectedQueueIsEmpty, $this->getResqueQueueService()->isEmpty('tasks-request'));
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

        $command = $this->createRequestCommand();
        $returnCode = $command->run(new ArrayInput([]), new StringOutput());

        $this->assertEquals(
            $expectedCommandReturnCode,
            $returnCode
        );

        $this->assertFalse($this->getResqueQueueService()->isEmpty('tasks-request'));
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

    /**
     * @return RequestCommand
     */
    private function createRequestCommand()
    {
        return new RequestCommand(
            $this->container->get('simplytestable.services.tasksservice'),
            $this->container->get('simplytestable.services.workerservice'),
            $this->container->get('simplytestable.services.resque.queueservice'),
            $this->container->get('simplytestable.services.resque.jobfactory')
        );
    }
}
