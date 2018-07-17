<?php
namespace App\Model\TaskDriver;

use App\Entity\Task\Output as TaskOutput;

class Response
{
    /**
     * @var boolean
     */
    private $hasSucceeded = true;

    /**
     * @var boolean
     */
    private $isRetryable = true;

    /**
     * @var boolean
     */
    private $isRetryLimitReached = false;

    /**
     * @var TaskOutput
     */
    private $taskOutput = null;

    /**
     * @var int
     */
    private $errorCount = 0;

    /**
     * @var int
     */
    private $warningCount = 0;

    /**
     * @var boolean
     */
    private $hasBeenSkipped = false;

    public function setHasSucceeded()
    {
        $this->hasSucceeded = true;
    }

    public function setHasBeenSkipped()
    {
        $this->hasBeenSkipped = true;
    }

    /**
     * @return boolean
     */
    public function hasBeenSkipped()
    {
        return $this->hasBeenSkipped;
    }

    public function setHasFailed()
    {
        $this->hasSucceeded = false;
    }

    /**
     * @return boolean
     */
    public function hasSucceeded()
    {
        return $this->hasSucceeded;
    }

    /**
     * @param boolean $retryable
     */
    public function setIsRetryable($retryable = true)
    {
        $this->isRetryable = $retryable;
    }

    /**
     * @return boolean
     */
    public function isRetryable()
    {
        return $this->isRetryable;
    }

    /**
     * @param boolean $isRetryLimitReached
     */
    public function setIsRetryLimitReached($isRetryLimitReached)
    {
        $this->isRetryLimitReached = $isRetryLimitReached;
    }

    /**
     * @return boolean
     */
    public function isRetryLimitReached()
    {
        return $this->isRetryLimitReached;
    }

    /**
     * @param TaskOutput $taskOutput
     */
    public function setTaskOutput(TaskOutput $taskOutput)
    {
        $this->taskOutput = $taskOutput;
    }

    /**
     * @return TaskOutput
     */
    public function getTaskOutput()
    {
        return $this->taskOutput;
    }

    /**
     * @param int $errorCount
     */
    public function setErrorCount($errorCount)
    {
        $this->errorCount = $errorCount;
    }

    /**
     * @return int
     */
    public function getErrorCount()
    {
        return $this->errorCount;
    }

    /**
     * @param int $warningCount
     */
    public function setWarningCount($warningCount)
    {
        $this->warningCount = $warningCount;
    }

    /**
     * @return int
     */
    public function getWarningCount()
    {
        return $this->warningCount;
    }
}
