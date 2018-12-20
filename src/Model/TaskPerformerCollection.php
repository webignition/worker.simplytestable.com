<?php

namespace App\Model;

use App\Services\TaskTypePerformer\TaskPerformerInterface;

class TaskPerformerCollection implements \Iterator, \Countable
{
    /**
     * @var TaskPerformerInterface[]
     */
    private $performers;

    /**
     * @var int
     */
    private $position = 0;

    public function __construct(array $performers)
    {
        $this->performers = $performers;
        $this->sort();

        $this->position = 0;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current(): TaskPerformerInterface
    {
        return $this->performers[$this->position];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->performers[$this->position]);
    }

    public function count(): int
    {
        return count($this->performers);
    }

    private function sort()
    {
        $sortIndex = [];

        foreach ($this->performers as $performerIndex => $performer) {
            $priority = $performer->getPriority();

            if (!array_key_exists($priority, $sortIndex)) {
                $sortIndex[$priority] = [];
            }

            $sortIndex[$priority][] = $performerIndex;
        }

        krsort($sortIndex);

        $sortedPeformers = [];

        foreach ($sortIndex as $priority => $performerIndices) {
            foreach ($performerIndices as $performerIndex) {
                $sortedPeformers[] = $this->performers[$performerIndex];
            }
        }

        $this->performers = $sortedPeformers;
    }
}
