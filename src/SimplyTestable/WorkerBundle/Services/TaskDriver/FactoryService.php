<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\WorkerBundle\Services\TaskDriver\TaskDriver;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use SimplyTestable\WorkerBundle\Services\StateService;

class FactoryService {
    
    /**
     * Collection of TaskDriver objects
     * 
     * @var array
     */
    private $taskDrivers = array();
    
    /**
     *
     * @var \SimplyTestable\WorkerBundle\Services\TaskTypeService 
     */
    private $taskTypeService;
    
    
    /**
     *
     * @param type $engines
     * @param TaskTypeService $taskTypeService 
     */
    public function __construct($engines, TaskTypeService $taskTypeService, StateService $stateService) {
        $this->taskTypeService = $taskTypeService;
        
        foreach ($engines as $identifier => $properties) {
            /* @var $engine TaskDriver */
            $engine = new $properties['class'];
            $engine->setStateService($stateService);
            
            foreach ($properties['task-types'] as $taskTypeName) {
                $engine->addTaskType($this->taskTypeService->fetch($taskTypeName));                
            }
            
            $this->registerTaskDriver($engine);
        }
    }
    
    
    /**
     *
     * @param TaskDriver $taskDriver
     * @return \SimplyTestable\WorkerBundle\Services\TaskDriver\Factory 
     */
    public function registerTaskDriver(TaskDriver $taskDriver) {
        foreach ($this->taskDrivers as $currentTaskDriver) {
            if ($currentTaskDriver == $taskDriver) {
                return $this;
            }
        }
        
        $this->taskDrivers[] = $taskDriver;
        return $this;
    }
    
    
    /**
     * Get a TaskDriver for a given Task, or false if no engine can be found
     *
     * @param Task $task
     * @return TaskDriver|boolean 
     */
    public function getTaskDriver(Task $task) {
        foreach ($this->taskDrivers as $taskDriver) {
            /* @var $taskDriver TaskDriver */
            if ($taskDriver->handles($task->getType())) {
                return $taskDriver;
            }
        }
        
        return false;
    }
    
}