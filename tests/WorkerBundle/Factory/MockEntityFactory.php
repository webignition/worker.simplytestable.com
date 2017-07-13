<?php

namespace Tests\WorkerBundle\Factory;

use Mockery\MockInterface;
use SimplyTestable\WorkerBundle\Entity\State;
use SimplyTestable\WorkerBundle\Entity\Task\Task;

class MockEntityFactory
{
    /**
     * @param int $id
     *
     * @return MockInterface|Task
     */
    public static function createTask($id, State $state)
    {
        $task = \Mockery::mock(Task::class);

        $task
            ->shouldReceive('getId')
            ->andReturn($id);

        $task
            ->shouldReceive('getState')
            ->andReturn($state);

        return $task;
    }

    /**
     * @param string $name
     *
     * @return MockInterface|State
     */
    public static function createState($name)
    {
        $state = \Mockery::mock(State::class);
        $state
            ->shouldReceive('getName')
            ->andReturn($name);

        return $state;
    }
}
