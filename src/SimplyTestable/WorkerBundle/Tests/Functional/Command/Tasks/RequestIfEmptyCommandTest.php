<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Command\Tasks;

use SimplyTestable\WorkerBundle\Command\Tasks\RequestIfEmptyCommand;
use SimplyTestable\WorkerBundle\Output\StringOutput;
use SimplyTestable\WorkerBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;

class RequestIfEmptyCommandTest extends BaseSimplyTestableTestCase
{
    /**
     * @dataProvider runDataProvider
     *
     * @param bool $hasEmptyQueue
     */
    public function testRun($hasEmptyQueue)
    {
        $this->clearRedis();

        if (!$hasEmptyQueue) {
            $this->getResqueQueueService()->enqueue(
                $this->getResqueJobFactoryService()->create(
                    'tasks-request'
                )
            );
        }

        $command = $this->createRequestIfEmptyCommand();
        $returnCode = $command->run(new ArrayInput([]), new StringOutput());

        $this->assertEquals(
            0,
            $returnCode
        );

        $this->assertFalse($this->getResqueQueueService()->isEmpty('tasks-request'));
        $this->assertEquals(1, $this->getResqueQueueService()->getQueueLength('tasks-request'));
    }

    public function runDataProvider()
    {
        return [
            'with empty queue' => [
                'hasEmptyQueue' => true,
            ],
            'with non-empty queue' => [
                'hasEmptyQueue' => false,
            ],
        ];
    }

    /**
     * @return RequestIfEmptyCommand
     */
    private function createRequestIfEmptyCommand()
    {
        return new RequestIfEmptyCommand(
            $this->container->get('simplytestable.services.resque.queueservice'),
            $this->container->get('simplytestable.services.resque.jobfactoryservice')
        );
    }
}
