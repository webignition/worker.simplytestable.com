<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Services\TaskDriver\TaskDriver;


class FactoryService {
    
    /**
     * Collection of TaskDriver objects
     * 
     * @var array
     */
    private $taskDrivers = array();
    
    
    /**
     *
     * @param type $engines
     * @param TaskTypeService $taskTypeService 
     */
    public function __construct($drivers, \Symfony\Component\DependencyInjection\Container $container) {
        
        foreach ($drivers as $identifier => $properties) {            
            /* @var $driver TaskDriver */
            $driver = new $properties['class'];
            $driver->setStateService($container->get('simplytestable.services.stateservice'));
            $driver->setWebResourceService($container->get('simplytestable.services.webresourceservice'));
            $driver->setHttpClientService($container->get('simplytestable.services.httpclientservice'));
            $driver->setTaskTypeService($container->get('simplytestable.services.tasktypeservice'));
            $driver->setLogger($container->get('logger'));
            $driver->setWebResourceTaskoutputService($container->get('simplytestable.services.webresourcetaskoutputservice'));
            $driver->setSerializer($container->get('serializer'));
            $driver->setTimeCachedTaskoutputService($container->get('simplytestable.services.timecachedtaskoutputservice'));
            
            if (isset($properties['properties'])) {
                $driverProperties = array();
                
                foreach ($properties['properties'] as $key => $value) {
                    if ($this->isDriverPropertyAsService($key)) {
                        $driverProperties[str_replace('.service', '', $key)] = $container->get($value);
                    } else {
                        $driverProperties[$key] = $value;
                    }                    
                }
                
                $driver->setProperties($driverProperties);
            }
            
            foreach ($properties['task-types'] as $taskTypeName) {
                $driver->addTaskType($container->get('simplytestable.services.tasktypeservice')->fetch($taskTypeName));                
            }
            
            $this->registerTaskDriver($driver);
        }
    }
    
    
    /**
     * 
     * @param string $key
     * @return boolean
     */
    private function isDriverPropertyAsService($key) {
        return preg_match('/\.service$/', $key) > 0;
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