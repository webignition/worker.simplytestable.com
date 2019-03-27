<?php

namespace App\Tests\Unit\Request\Task;

use App\Entity\Task\Task;
use App\Request\Task\CancelRequest;

class CancelRequestTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $task = \Mockery::mock(Task::class);

        $cancelRequest = new CancelRequest($task);

        $this->assertSame($task, $cancelRequest->getTask());
    }
}
