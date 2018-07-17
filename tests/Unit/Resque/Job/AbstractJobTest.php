<?php

namespace App\Tests\Unit\Resque\Job;

use Mockery\Mock;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ResqueBundle\Resque\ContainerAwareJob;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class AbstractJobTest extends \PHPUnit\Framework\TestCase
{
    protected function createJob(
        ContainerAwareJob $job,
        $args,
        $commandClassName,
        $expectedCommandReturnCode,
        LoggerInterface $logger
    ) {
        $job->args = array_merge($args, [
            'kernel.root_dir' => 'foo',
            'kernel.environment' => 'test',
        ]);

        /* @var Mock|Command $command */
        $command = \Mockery::mock($commandClassName);
        $command
            ->shouldReceive('run')
            ->andReturn($expectedCommandReturnCode);

        /* @var Mock|ContainerInterface $container */
        $container = \Mockery::mock(ContainerInterface::class);
        $container
            ->shouldReceive('get')
            ->with($commandClassName)
            ->andReturn($command);

        $container
            ->shouldReceive('get')
            ->with('logger')
            ->andReturn($logger);

        /* @var Mock|KernelInterface $kernel */
        $kernel = \Mockery::mock(KernelInterface::class);
        $kernel
            ->shouldReceive('getContainer')
            ->andReturn($container);

        $reflectionClass = new ReflectionClass(ContainerAwareJob::class);

        $reflectionProperty = $reflectionClass->getProperty('kernel');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($job, $kernel);
    }
}
