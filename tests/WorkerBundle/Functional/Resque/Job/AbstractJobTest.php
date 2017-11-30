<?php

namespace Tests\WorkerBundle\Functional\Resque\Job;

use SimplyTestable\WorkerBundle\Resque\Job\Job;
use SimplyTestable\WorkerBundle\Services\Resque\JobFactory as ResqueJobFactory;
use Symfony\Component\Console\Command\Command;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;

abstract class AbstractJobTest extends BaseSimplyTestableTestCase
{
    /**
     * @param array $args
     * @param string $queue
     * @param Command $command
     *
     * @return Job
     */
    public function createJob($args, $queue, Command $command)
    {
        $resqueJobFactory = $this->container->get(ResqueJobFactory::class);

        $job = $resqueJobFactory->create($queue, $args);

        $job->setKernelOptions([
            'kernel.root_dir' => $this->container->getParameter('kernel.root_dir'),
            'kernel.environment' => $this->container->getParameter('kernel.environment'),
            'command' => $command,
        ]);

        return $job;
    }
}
