<?php

namespace App\Tests\Unit\Event;

use App\Entity\Task\Task;
use App\Event\TaskEvent;

class TaskEventTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $task = new Task();

        $taskEvent = new TaskEvent($task);

        $this->assertEquals($task, $taskEvent->getTask());
    }
}
