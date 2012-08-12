<?php

namespace SimplyTestable\WorkerBundle\Services\TaskEngine;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;

abstract class TaskEngine {
    
    /**
     * Collection of task types that this task engine can handle
     * 
     * @var array
     */
    private $taskTypes;
    
    
    /**
     * @param Task $task
     * @return string 
     */
    abstract public function perform(Task $task);   
    
    
    /**
     *
     * @return string
     */
    public function getOutput() {
        return $this->output;
    }
    
    
    /**
     *
     * @param TaskType $taskType 
     */
    public function addTaskType(TaskType $taskType) {
        if (!$this->handles($taskType)) {
            $this->taskTypes[] = $taskType;
        }
    }
    
    
    /**
     *
     * @param TaskType $taskType
     * @return boolean 
     */
    public function handles(TaskType $taskType) {
        foreach ($this->taskTypes as $currentTaskType) {
            /* @var $currentTaskType TaskType */
            if ($currentTaskType->equals($taskType)) {
                return true;
            }
        }
        
        return false;
    }
    
    
}