<?php

namespace SimplyTestable\WorkerBundle\Tests\Resque\Job;

use SimplyTestable\WorkerBundle\Resque\Job\Job;
use SimplyTestable\WorkerBundle\Tests\BaseSimplyTestableTestCase;

abstract class JobTest extends BaseSimplyTestableTestCase {

    abstract protected function getArgs();
    abstract protected function getExpectedQueue();


    public function testJobRuns() {
        $this->assertTrue($this->getJob()->run($this->getArgs()));
    }


    public function testJobQueueName() {
        $this->assertEquals($this->getExpectedQueue(), $this->getJob()->queue);
    }


    /**
     * @return Job
     */
    protected function getJob() {
        $classNameParts = explode('\\', str_replace('\\Tests', '', get_class($this)));
        array_pop($classNameParts);
        $classNameParts[count($classNameParts) - 1] .= 'Job';

        $className = '\\' . implode('\\', $classNameParts);

        $job = new $className(array_merge(
            [
                'kernel.root_dir' => $this->container->get('kernel')->getRootDir(),
                'kernel.debug' => true,
                'kernel.environment' => 'test',
                'returnCode' => 0,
            ],
            $this->getArgs()
        ));

        return $job;
    }


}
