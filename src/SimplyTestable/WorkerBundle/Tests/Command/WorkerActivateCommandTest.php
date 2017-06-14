<?php

namespace SimplyTestable\WorkerBundle\Tests\Command;

use SimplyTestable\WorkerBundle\Command\WorkerActivateCommand;
use SimplyTestable\WorkerBundle\Services\WorkerService;

class WorkerActivateCommandTest extends ConsoleCommandBaseTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAdditionalCommands()
    {
        return array(
            new WorkerActivateCommand()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected static function getServicesToMock()
    {
        return [
            'simplytestable.services.workerservice',
        ];
    }

    public function testExecuteInMaintenanceReadOnlyMode()
    {
        $this->getWorkerService()->setReadOnly();

        $this->assertEquals(
            WorkerActivateCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $this->executeCommand('simplytestable:worker:activate')
        );
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param int $activationResult
     * @param int $expectedReturnCode
     */
    public function testExecute($activationResult, $expectedReturnCode)
    {
        $this->container->get('simplytestable.services.workerservice')
            ->shouldReceive('activate')
            ->andReturn($activationResult);

        $this->assertEquals($expectedReturnCode, $this->executeCommand('simplytestable:worker:activate'));
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'success' => [
                'activationResult' => 0,
                'expectedReturnCode' => 0,
            ],
            'unknown error' => [
                'activationResult' => 1,
                'expectedReturnCode' => WorkerActivateCommand::RETURN_CODE_UNKNOWN_ERROR,
            ],
            'http 404' => [
                'activationResult' => 404,
                'expectedReturnCode' => 404,
            ],
            'curl 28' => [
                'activationResult' => 28,
                'expectedReturnCode' => 28,
            ],
        ];
    }
}
