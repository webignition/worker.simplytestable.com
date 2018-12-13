<?php

namespace App\Model;

use App\Services\TaskTypePreparer\TaskPreparerInterface;

class TaskPreparerCollection implements \Iterator, \Countable
{
    /**
     * @var TaskPreparerInterface[]
     */
    private $preparers;

    /**
     * @var int
     */
    private $position = 0;

    public function __construct(array $preparers)
    {
        $this->preparers = $preparers;
        $this->sort();

        $this->position = 0;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current(): TaskPreparerInterface
    {
        return $this->preparers[$this->position];
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
        return isset($this->preparers[$this->position]);
    }

    public function count(): int
    {
        return count($this->preparers);
    }

    private function sort()
    {
        $sortIndex = [];

        foreach ($this->preparers as $preparerIndex => $preparer) {
            $priority = $preparer->getPriority();

            if (!array_key_exists($priority, $sortIndex)) {
                $sortIndex[$priority] = [];
            }

            $sortIndex[$priority][] = $preparerIndex;
        }

        krsort($sortIndex);

        $sortedPreparers = [];

        foreach ($sortIndex as $priority => $preparerIndices) {
            foreach ($preparerIndices as $preparerIndex) {
                $sortedPreparers[] = $this->preparers[$preparerIndex];
            }
        }

        $this->preparers = $sortedPreparers;
    }
}
