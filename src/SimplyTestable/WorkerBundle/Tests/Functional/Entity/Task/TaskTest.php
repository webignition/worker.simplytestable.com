<?php

namespace SimplyTestable\WorkerBundle\Tests\Functional\Entity\Task;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Tests\Functional\BaseTestCase;

class TaskTest extends BaseTestCase {


    /**
     * @group standard
     */
    public function testCheckIfParameterIsTrue() {
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
