<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\WorkerBundle\Services\TaskTypeService;
use SimplyTestable\WorkerBundle\Services\StateService;
use SimplyTestable\WorkerBundle\Services\WebResourceService;
use Symfony\Component\HttpKernel\Log\LoggerInterface as Logger;
use SimplyTestable\WorkerBundle\Model\TaskDriver\Response as TaskDriverResponse;

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
     * @var \SimplyTestable\WorkerBundle\Services\WebResourceService 
     */    
    private $webResourceService;
    
    
    /**
     *
     * @var \SimplyTestable\WorkerBundle\Services\TaskTypeService
     */    
    private $taskTypeService;
    
    /**
     *
     * @var Logger 
     */
    private $logger;
    
    
    /**
     * Arbitrary properties to be used by a concrete implementation
     * 
     * @var array
     */
    private $properties;  
    
    /**
     *
     * @var TaskDriverResponse
     */
    protected $response = null;
    
    
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
     *
     * @param Logger $logger 
     */
    public function setLogger(Logger $logger) {
        $this->logger = $logger;
    }
    
    /**
     *
     * @return Logger
     */
    public function getLogger() {
        return $this->logger;
    }
    
    
    /**
     * @param Task $task
     * @return TaskDriverResponse
     */
    public function perform(Task $task) {        
        $this->response = new TaskDriverResponse();
        
        $rawOutput = $this->execute($task);
        
        $output = new \SimplyTestable\WorkerBundle\Entity\Task\Output();
        $output->setOutput($rawOutput);
        $output->setContentType($this->getOutputContentType());
        $output->setState($this->stateService->fetch(self::OUTPUT_STARTING_STATE));
        $output->setErrorCount($this->response->getErrorCount());
        
        $this->response->setTaskOutput($output);       
        
        return $this->response;
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