<?php
namespace SimplyTestable\WorkerBundle\Model\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Output as TaskOutput;

/**
 * 
 */
class Response
{
    /**
     *
     * @var boolean
     */
    private $hasSucceeded = true;
    
    
    /**
     *
     * @var boolean
     */
    private $isRetryable = true;
    
    
    /**
     *
     * @var boolean
     */
    private $isRetryLimitReached = false;
    
    
    /**
     *
     * @var TaskOutput
     */
    private $taskOutput = null;  
    
    
    /**
     *
     * @var int
     */
    private $errorCount = null;
    
    
    /**
     *
     * @var int
     */
    private $warningCount = null;
    
    
    /**
     *
     * @var boolean
     */
    private $hasBeenSkipped = false;
    

    /**
     * State that task performance succeeded
     *  
     */
    public function setHasSucceeded() {
        $this->hasSucceeded = true;
    }
    
    
    /**
     * State that task performance was skipped
     * Most commonly used when the type of content being tested
     * is not relevant for the type of test being performed
     *  
     */
    public function setHasBeenSkipped() {
        $this->hasBeenSkipped = true;
    }
    
    
    /**
     * Was task performance skipped?
     * 
     * @return boolean
     */
    public function hasBeenSkipped() {
        return $this->hasBeenSkipped;
    }
    
    
    /**
     * State that task performance failed
     *  
     */
    public function setHasFailed() {
        $this->hasSucceeded = false;
    }
    
    
    /**
     * Has the task performance succeeded?
     * 
     * @return boolean
     */
    public function hasSucceeded() {
        return $this->hasSucceeded;
    }     
    
    
    /**
     * State whether task performance can be retried
     *
     * @param boolean $retryable 
     */
    public function setIsRetryable($retryable = true) {
        $this->isRetryable = $retryable;
    }
    
    
    /**
     * Can task performance be retried?
     * 
     * @return boolean
     */
    public function isRetryable() {
        return $this->isRetryable;
    }
    
    
    /**
     * State whether the retry limit has been reached
     * 
     * @param boolean $isRetryLimitReached 
     */
    public function setIsRetryLimitReached($isRetryLimitReached) {
        $this->isRetryLimitReached = $isRetryLimitReached;
    }   
    
    
    /**
     * Has the retry limit been reached?
     * 
     * @return boolean
     */
    public function isRetryLimitReached() {
        return $this->isRetryLimitReached;
    }

    
    /**
     *
     * @param TaskOutput $taskOutput 
     */
    public function setTaskOutput(TaskOutput $taskOutput) {
        $this->taskOutput = $taskOutput;
    }
    
    
    /**
     *
     * @return TaskOutput 
     */
    public function getTaskOutput() {
        return $this->taskOutput;
    }
    
    /**
     *
     * @param int $errorCount 
     */
    public function setErrorCount($errorCount) {
        $this->errorCount = $errorCount;        
    }
    
    /**
     *
     * @return int
     */
    public function getErrorCount() {
        return $this->errorCount;
    }
    
    
    /**
     *
     * @param int $warningCount 
     */
    public function setWarningCount($warningCount) {
        $this->warningCount = $warningCount;        
    }
    
    /**
     *
     * @return int
     */
    public function getWarningCount() {
        return $this->warningCount;
    }   
}