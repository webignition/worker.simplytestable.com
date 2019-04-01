<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Task\Output;
use App\Entity\Task\Task;
use App\Model\Source;
use App\Model\Task\TypeInterface;

class TaskTest extends \PHPUnit\Framework\TestCase
{
    public function testReset()
    {
        $type = \Mockery::mock(TypeInterface::class);
        $url = 'http://example.com/';
        $output = \Mockery::mock(Output::class);

        $source = \Mockery::mock(Source::class);
        $source
            ->shouldReceive('getUrl')
            ->andReturn($url);
        $source
            ->shouldReceive('toArray')
            ->andReturn([]);

        $task = Task::create($type, $url);
        $task->setState(Task::STATE_PREPARING);
        $task->addSource($source);
        $task->setOutput($output);

        $this->assertEquals(Task::STATE_PREPARING, $task->getState());
        $this->assertSame($output, $task->getOutput());
        $this->assertNotEmpty($task->getSources());

        $task->reset();

        $this->assertEquals(Task::STATE_QUEUED, $task->getState());
        $this->assertEmpty($task->getOutput());
        $this->assertEmpty($task->getSources());
    }
}
