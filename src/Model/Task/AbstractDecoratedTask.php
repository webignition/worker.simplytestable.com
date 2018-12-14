<?php

namespace App\Model\Task;

use App\Entity\Task\Output;
use App\Entity\Task\Task;

class AbstractDecoratedTask extends Task
{
    /**
     * @var Task
     */
    private $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function getId(): ?int
    {
        return parent::getId();
    }

    public function setState(string $state)
    {
        parent::setState($state);
    }

    public function getState(): string
    {
        return parent::getState();
    }

    public function getType(): Type
    {
        return parent::getType();
    }

    public function setOutput(Output $output)
    {
        parent::setOutput($output);
    }

    public function getOutput(): ?Output
    {
        return parent::getOutput();
    }

    public function getParametersHash(): string
    {
        return parent::getParametersHash();
    }

    public function getParameters(): Parameters
    {
        return parent::getParameters();
    }

    public function setParentTask(Task $parentTask)
    {
        parent::setParentTask($parentTask);
    }

    public function getParentTask(): ?Task
    {
        return parent::getParentTask();
    }

    public function setStartDateTime(\DateTime $startDateTime)
    {
        parent::setStartDateTime($startDateTime);
    }

    public function setEndDateTime(\DateTime $endDateTime)
    {
        parent::setEndDateTime($endDateTime);
    }

    public function jsonSerialize(): array
    {
        return parent::jsonSerialize();
    }
}
