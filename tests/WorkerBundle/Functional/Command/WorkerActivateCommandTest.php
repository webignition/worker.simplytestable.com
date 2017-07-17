<?php

namespace Tests\WorkerBundle\Functional\Command;

use SimplyTestable\WorkerBundle\Command\WorkerActivateCommand;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;

class WorkerActivateCommandTest extends BaseSimplyTestableTestCase
{
    /**
     * @var WorkerActivateCommand
     */
    private $command;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = $this->container->get(WorkerActivateCommand::class);
    }

    public function testRunInMaintenanceReadOnlyMode()
    {
        $this->container->get(WorkerService::class)->setReadOnly();

        $returnCode = $this->command->run(new ArrayInput([]), new BufferedOutput());

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
        $this->container->get(WorkerService::class)
            ->setActivateResult($activationResult);

        $returnCode = $this->command->run(new ArrayInput([]), new BufferedOutput());

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
