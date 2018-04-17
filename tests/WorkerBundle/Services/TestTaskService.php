<?php

namespace Tests\WorkerBundle\Services;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Services\TaskService;

class TestTaskService extends TaskService
{
    /**
     * @var mixed
     */
    private $performResult;

    /**
     * @var \Exception
     */
    private $performException;

    /**
     * @param mixed $performResult
     */
    public function setPerformResult($performResult)
    {
        $this->performResult = $performResult;
    }

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
        if (!is_null($this->performResult)) {
            $performResult = $this->performResult;
            $this->performResult = null;

            return $performResult;
        }

        if (!empty($this->performException)) {
            throw $this->performException;
        }

        return parent::perform($task);
    }
}
