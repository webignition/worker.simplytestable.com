<?php

namespace Tests\WorkerBundle\Functional\Command;

use SimplyTestable\WorkerBundle\Command\WorkerActivateCommand;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;

class WorkerActivateCommandTest extends BaseSimplyTestableTestCase
{
    /**
     * {@inheritdoc}
     */
    protected static function getServicesToMock()
    {
        return [
            'simplytestable.services.workerservice',
        ];
    }

    public function testRunInMaintenanceReadOnlyMode()
    {
        $this->getWorkerService()->setReadOnly();

        $command = new WorkerActivateCommand(
            $this->container->get('simplytestable.services.workerservice')
        );

        $returnCode = $command->run(new ArrayInput([]), new StringOutput());

        $this->assertEquals(
            WorkerActivateCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }

    /**
     * @dataProvider runDataProvider
     *
     * @param int $activationResult
     * @param int $expectedReturnCode
     */
    public function testRun($activationResult, $expectedReturnCode)
    {
        $this->container->get('simplytestable.services.workerservice')
            ->shouldReceive('activate')
            ->andReturn($activationResult);

        $command = new WorkerActivateCommand(
            $this->container->get('simplytestable.services.workerservice')
        );

        $returnCode = $command->run(new ArrayInput([]), new StringOutput());

        $this->assertEquals($expectedReturnCode, $returnCode);
    }

    /**
     * @return array
     */
    public function runDataProvider()
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
