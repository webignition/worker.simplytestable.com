<?php

namespace SimplyTestable\WorkerBundle\Tests\Command\Tasks;

use SimplyTestable\WorkerBundle\Command\Tasks\RequestIfEmptyCommand;
use SimplyTestable\WorkerBundle\Tests\Command\ConsoleCommandBaseTestCase;

class RequestIfEmptyCommandTest extends ConsoleCommandBaseTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getAdditionalCommands()
    {
        return array(
            new RequestIfEmptyCommand()
        );
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param bool $hasEmptyQueue
     */
    public function testExecute($hasEmptyQueue)
    {
        $this->clearRedis();

        if (!$hasEmptyQueue) {
            $this->getResqueQueueService()->enqueue(
                $this->getResqueJobFactoryService()->create(
                    'tasks-request'
                )
            );
        }

        $this->assertEquals(
            0,
            $this->executeCommand('simplytestable:tasks:requestifempty')
        );

        $this->assertFalse($this->getResqueQueueService()->isEmpty('tasks-request'));
        $this->assertEquals(1, $this->getResqueQueueService()->getQueueLength('tasks-request'));
    }

    public function executeDataProvider()
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
}
