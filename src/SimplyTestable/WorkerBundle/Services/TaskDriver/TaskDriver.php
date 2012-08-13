<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\WorkerBundle\Services\StateService;

abstract class TaskDriver {
    
    const OUTPUT_STARTING_STATE = 'taskoutput-queued';
    
    /**
     * Collection of task types that this task engine can handle
     * 
     * @var array
     */
    private $taskTypes = array();
    
    /**
     *
     * @var \SimplyTestable\WorkerBundle\Services\StateService 
     */
    private $stateService;
    
    
    /**
     *
     * @param StateService $stateService 
     */
    public function setStateService(StateService $stateService) {
        $this->stateService = $stateService;
    }
    
    
    /**
     * @param Task $task
     * @return \SimplyTestable\WorkerBundle\Entity\Task\Output 
     */
    public function perform(Task $task) {
        $rawOutput = $this->execute($task);
        $output = new \SimplyTestable\WorkerBundle\Entity\Task\Output();
        $output->setOutput($rawOutput);
        $output->setState($this->stateService->fetch(self::OUTPUT_STARTING_STATE));
        
        return $output;
    }
    
    
    /**
     * @return string 
     */
    abstract protected function execute(Task $task);


    
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