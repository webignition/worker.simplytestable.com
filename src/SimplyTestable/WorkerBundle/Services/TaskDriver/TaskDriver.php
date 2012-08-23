<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use SimplyTestable\WorkerBundle\Services\StateService;
use SimplyTestable\WorkerBundle\Services\WebResourceService;

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
     * @var \SimplyTestable\WorkerBundle\Services\WebResource\Service 
     */    
    private $webResourceService;
    
    
    /**
     *
     * @var \SimplyTestable\WorkerBundle\Services\TaskTypeService
     */    
    private $taskTypeService;
    
    
    /**
     * Arbitrary properties to be used by a concrete implementation
     * 
     * @var array
     */
    private $properties;
    
    
    /**
     *
     * @param array $properties 
     */
    public function setProperties($properties) {
        $this->properties = $properties;
    }
    
    
    /**
     *
     * @param string $propertyName
     * @return mixed
     */
    public function getProperty($propertyName) {
        return (isset($this->properties[$propertyName])) ? $this->properties[$propertyName] : null;
    }
    
    
    
    /**
     *
     * @param StateService $stateService 
     */
    public function setStateService(StateService $stateService) {
        $this->stateService = $stateService;
    }
    
    
    /**
     *
     * @param WebResourceService $webResourceService 
     */
    public function setWebResourceService(WebResourceService $webResourceService) {
        $this->webResourceService = $webResourceService;
    } 
    
    
    /**
     *
     * @param TaskTypeService $taskTypeService 
     */
    public function setTaskTypeService(TaskTypeService $taskTypeService) {
        $this->taskTypeService = $taskTypeService;
    }    
    
    
    /**
     * @return \SimplyTestable\WorkerBundle\Services\WebResourceService
     */
    public function getWebResourceService() {
        return $this->webResourceService;
    }
    
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\TaskTypeService 
     */
    public function getTaskTypeService() {
        return $this->taskTypeService;
    }
    
    
    /**
     * @param Task $task
     * @return \SimplyTestable\WorkerBundle\Entity\Task\Output 
     */
    public function perform(Task $task) {        
        $rawOutput = $this->execute($task);
        $output = new \SimplyTestable\WorkerBundle\Entity\Task\Output();
        $output->setOutput($rawOutput);
        $output->setContentType($this->getOutputContentType());
        $output->setState($this->stateService->fetch(self::OUTPUT_STARTING_STATE));
        
        return $output;
    }
    
    
    /**
     * @return string 
     */
    abstract protected function execute(Task $task);
    
    
    /**
     * @return \webignition\InternetMediaType\InternetMediaType
     */
    abstract protected function getOutputContentType();


    
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