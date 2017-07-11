<?php
namespace SimplyTestable\WorkerBundle\Model;

use SimplyTestable\WorkerBundle\Entity\Task\Task;

class TaskCollection implements \JsonSerializable, \Iterator
{
    /**
     * @var int
     */
    private $position = 0;

    /**
     * @var Task[]
     */
    private $tasks;

    /**
     * @param Task $task
     */
    public function add(Task $task)
    {
        $this->tasks[] = $task;
    }

    /**
     * @return Task[]
     */
    public function jsonSerialize()
    {
        return $this->tasks;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        return $this->tasks[$this->position];
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return isset($this->tasks[$this->position]);
    }
}
