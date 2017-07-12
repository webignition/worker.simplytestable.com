<?php

namespace Tests\WorkerBundle\Factory;

use Mockery\MockInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerFactory
{
    /**
     * @param array $services
     *
     * @return MockInterface|ContainerInterface
     */
    public static function create($services)
    {
        $container = \Mockery::mock(ContainerInterface::class);

        foreach ($services as $serviceId => $service) {
            $container
                ->shouldReceive('get')
                ->with($serviceId)
                ->andReturn($service);
        }

        return $container;
    }
}
