<?php

namespace Tests\WorkerBundle\Services;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Services\TaskService;

class TestTaskService extends TaskService
{
    /**
     * @var \Exception
     */
    private $performException;

    /**
     * @param \Exception $performException
     */
    public function setPerformException(\Exception $performException)
    {
        $this->performException = $performException;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function perform(Task $task)
    {
        if (!empty($this->performException)) {
            throw $this->performException;
        }

        parent::perform($task);
    }
}
