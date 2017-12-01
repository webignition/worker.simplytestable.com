<?php

namespace Tests\WorkerBundle\Functional\Resque\Job;

use SimplyTestable\WorkerBundle\Resque\Job\Job;
use webignition\ResqueJobFactory\ResqueJobFactory;
use Tests\WorkerBundle\Functional\BaseSimplyTestableTestCase;

abstract class AbstractJobTest extends BaseSimplyTestableTestCase
{
    /**
     * @param array $args
     * @param string $queue
     *
     * @return Job
     */
    public function createJob($args, $queue)
    {
        $resqueJobFactory = $this->container->get(ResqueJobFactory::class);

        $job = $resqueJobFactory->create($queue, $args);

        $job->setKernelOptions([
            'kernel.root_dir' => $this->container->getParameter('kernel.root_dir'),
            'kernel.environment' => $this->container->getParameter('kernel.environment'),
        ]);

        return $job;
    }
}
