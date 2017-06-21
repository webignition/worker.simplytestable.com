<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Command\Tasks;

use SimplyTestable\WorkerBundle\Command\Tasks\RequestCommand;
use SimplyTestable\WorkerBundle\Tests\Functional\Command\ConsoleCommandBaseTestCase;
use SimplyTestable\WorkerBundle\Tests\Factory\ConnectExceptionFactory;

class RequestCommandTest extends ConsoleCommandBaseTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAdditionalCommands()
    {
        return array(
            new RequestCommand()
        );
    }

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

        $this->assertEquals(
            RequestCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $this->executeCommand('simplytestable:tasks:request')
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

        $this->assertEquals(
            $expectedCommandReturnCode,
            $this->executeCommand('simplytestable:tasks:request')
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


        $this->assertEquals(
            $expectedCommandReturnCode,
            $this->executeCommand('simplytestable:tasks:request')
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
}
