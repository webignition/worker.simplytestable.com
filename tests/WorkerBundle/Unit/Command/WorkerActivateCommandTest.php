<?php

namespace Tests\WorkerBundle\Unit\Command;

use SimplyTestable\WorkerBundle\Command\WorkerActivateCommand;
use SimplyTestable\WorkerBundle\Services\WorkerService;
use Symfony\Component\Console\Output\NullOutput;
use Tests\WorkerBundle\Factory\MockFactory;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * @group Command/WorkerActivateCommand
 */
class WorkerActivateCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @throws \Exception
     */
    public function testRunInMaintenanceReadOnlyMode()
    {
        $command = $this->createWorkerActivateCommand([
            WorkerService::class => MockFactory::createWorkerService([
                'isMaintenanceReadOnly' => [
                    'return' => true,
                ]
            ]),
        ]);

        $returnCode = $command->run(new ArrayInput([]), new NullOutput());

        $this->assertEquals(
            WorkerActivateCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE,
            $returnCode
        );
    }

    /**
     * @param array $services
     *
     * @return WorkerActivateCommand
     */
    private function createWorkerActivateCommand($services = [])
    {
        if (!isset($services[WorkerService::class])) {
            $services[WorkerService::class] = MockFactory::createWorkerService();
        }

        return new WorkerActivateCommand(
            $services[WorkerService::class]
        );
    }

    /**
     * @dataProvider runDataProvider
     *
     * @param WorkerService $workerService
     * @param int $expectedReturnCode
     * @throws \Exception
     */
    public function testRun(WorkerService $workerService, $expectedReturnCode)
    {
        $command = $this->createWorkerActivateCommand([
            WorkerService::class => $workerService,
        ]);

        $returnCode = $command->run(new ArrayInput([]), new NullOutput());

        $this->assertEquals($expectedReturnCode, $returnCode);
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        return [
            'success' => [
                'workerService'=> MockFactory::createWorkerService([
                    'isMaintenanceReadOnly' => [
                        'return' => false,
                    ],
                    'activate' => [
                        'return' => 0,
                    ],
                ]),
                'expectedReturnCode' => 0,
            ],
            'unknown error' => [
                'workerService'=> MockFactory::createWorkerService([
                    'isMaintenanceReadOnly' => [
                        'return' => false,
                    ],
                    'activate' => [
                        'return' => 1,
                    ],
                ]),
                'expectedReturnCode' => WorkerActivateCommand::RETURN_CODE_UNKNOWN_ERROR,
            ],
            'http 404' => [
                'workerService'=> MockFactory::createWorkerService([
                    'isMaintenanceReadOnly' => [
                        'return' => false,
                    ],
                    'activate' => [
                        'return' => 404,
                    ],
                ]),
                'expectedReturnCode' => 404,
            ],
            'curl 28' => [
                'workerService'=> MockFactory::createWorkerService([
                    'isMaintenanceReadOnly' => [
                        'return' => false,
                    ],
                    'activate' => [
                        'return' => 28,
                    ],
                ]),
                'expectedReturnCode' => 28,
            ],
        ];
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
