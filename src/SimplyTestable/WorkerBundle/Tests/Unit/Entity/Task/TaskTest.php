<?php

namespace SimplyTestable\WorkerBundle\Tests\Unit\Entity\Task;

use SimplyTestable\WorkerBundle\Entity\Task\Task;

class TaskTest extends \PHPUnit_Framework_TestCase
{
    public function testIsTrue()
    {
        $parameters = array(
            'one' => true,
            'two' => false,
            'three' => 'bar'
        );

        $task = new Task();
        $task->setParameters(json_encode($parameters));

        $this->assertTrue($task->isTrue('one'));
        $this->assertFalse($task->isTrue('two'));
        $this->assertFalse($task->isTrue('three'));
        $this->assertFalse($task->isTrue('unset-key'));
    }
}
