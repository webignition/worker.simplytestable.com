<?php

namespace App\Model\Task;

use App\Entity\Task\Task;

class DecoratedTask extends AbstractDecoratedTask
{
    /**
     * @var Type
     */
    private $type;

    public function __construct(Task $task, Type $type)
    {
        parent::__construct($task);
        $this->type = $type;
    }

    public function getType(): Type
    {
        return $this->type;
    }
}
