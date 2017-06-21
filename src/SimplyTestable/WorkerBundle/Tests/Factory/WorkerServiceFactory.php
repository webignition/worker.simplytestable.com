<?php

namespace SimplyTestable\WorkerBundle\Tests\Factory;

use Mockery\MockInterface;
use SimplyTestable\WorkerBundle\Services\WorkerService;

class WorkerServiceFactory
{
    /**
     * @param bool $isMaintenanceReadOnly
     *
     * @return MockInterface|WorkerService
     */
    public static function create($isMaintenanceReadOnly)
    {
        $workerService = \Mockery::mock(WorkerService::class);
        $workerService
            ->shouldReceive('isMaintenanceReadOnly')
            ->andReturn($isMaintenanceReadOnly);

        return $workerService;
    }
}
