<?php

namespace SimplyTestable\WorkerBundle\Tests\Request\Task;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Request\Task\CancelRequest;

class CancelRequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider createDataProvider
     *
     * @param Task|null $task
     * @param Task|null $expectedTask
     * @param bool $expectedIsValid
     */
    public function testCreate($task, $expectedTask, $expectedIsValid)
    {
        $cancelRequest = new CancelRequest($task);

        $this->assertEquals($expectedTask, $cancelRequest->getTask());
        $this->assertEquals($expectedIsValid, $cancelRequest->isValid());
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        $task = new Task();

        return [
            'invalid' => [
                'task' => null,
                'expectedTask' => null,
                'expectedIsValid' => false,
            ],
            'valid' => [
                'task' => $task,
                'expectedTask' => $task,
                'expectedIsValid' => true,
            ],
        ];
    }
}
