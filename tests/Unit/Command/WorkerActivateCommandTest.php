<?php

namespace App\Tests\Unit\Command;

use App\Command\WorkerActivateCommand;
use App\Services\WorkerService;
use Symfony\Component\Console\Output\NullOutput;
use App\Tests\Factory\MockFactory;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * @group Command/WorkerActivateCommand
 */
class WorkerActivateCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider runDataProvider
     *
     * @param WorkerService $workerService
     * @param int $expectedReturnCode
     * @throws \Exception
     */
    public function testRun(WorkerService $workerService, int $expectedReturnCode)
    {
        $command = $this->createWorkerActivateCommand([
            WorkerService::class => $workerService,
        ]);

        $returnCode = $command->run(new ArrayInput([]), new NullOutput());

        $this->assertEquals($expectedReturnCode, $returnCode);
    }

    public function runDataProvider(): array
    {
        return [
            'success' => [
                'workerService'=> MockFactory::createWorkerService([
                    'activate' => [
                        'return' => 0,
                    ],
                ]),
                'expectedReturnCode' => 0,
            ],
            'unknown error' => [
                'workerService'=> MockFactory::createWorkerService([
                    'activate' => [
                        'return' => 1,
                    ],
                ]),
                'expectedReturnCode' => WorkerActivateCommand::RETURN_CODE_UNKNOWN_ERROR,
            ],
            'http 404' => [
                'workerService'=> MockFactory::createWorkerService([
                    'activate' => [
                        'return' => 404,
                    ],
                ]),
                'expectedReturnCode' => 404,
            ],
            'curl 28' => [
                'workerService'=> MockFactory::createWorkerService([
                    'activate' => [
                        'return' => 28,
                    ],
                ]),
                'expectedReturnCode' => 28,
            ],
        ];
    }

    private function createWorkerActivateCommand(array $services = []): WorkerActivateCommand
    {
        if (!isset($services[WorkerService::class])) {
            $services[WorkerService::class] = MockFactory::createWorkerService();
        }

        return new WorkerActivateCommand(
            $services[WorkerService::class]
        );
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
