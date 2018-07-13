<?php

namespace Tests\AppBundle\Unit\Request\Task;

use SimplyTestable\AppBundle\Entity\Task\Task;
use SimplyTestable\AppBundle\Request\Task\CancelRequest;

class CancelRequestTest extends \PHPUnit\Framework\TestCase
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
