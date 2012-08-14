<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\WorkerBundle\Services\TaskDriver\TaskDriver;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use SimplyTestable\WorkerBundle\Services\StateService;
use SimplyTestable\WorkerBundle\Services\WebResourceService;

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
     * @var \SimplyTestable\WorkerBundle\Services\WebResourceService
     */    
    private $webResourceService;
    
    
    /**
     *
     * @param type $engines
     * @param TaskTypeService $taskTypeService 
     */
    public function __construct(
            $drivers,
            TaskTypeService $taskTypeService,
            StateService $stateService,
            WebResourceService $webResourceService
        ) {
        
        $this->taskTypeService = $taskTypeService;
        $this->webResourceService = $webResourceService;
        
        foreach ($drivers as $identifier => $properties) {
            /* @var $driver TaskDriver */
            $driver = new $properties['class'];
            $driver->setStateService($stateService);
            
            foreach ($properties['task-types'] as $taskTypeName) {
                $driver->addTaskType($this->taskTypeService->fetch($taskTypeName));                
            }
            
            $this->registerTaskDriver($driver);
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