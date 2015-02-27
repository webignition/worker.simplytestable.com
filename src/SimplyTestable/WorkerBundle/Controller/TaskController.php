<?php

namespace SimplyTestable\WorkerBundle\Controller;

use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputArgument;

use SimplyTestable\WorkerBundle\Entity\Task\Task;

class TaskController extends BaseController
{
    public function __construct() {        
        $this->setInputDefinitions(array(
            'createAction' => new InputDefinition(array(
                new InputArgument('type', InputArgument::REQUIRED, 'Name of task type, case insensitive'),
                new InputArgument('url', InputArgument::REQUIRED, 'URL of web page against which the task is to be performed')
            )),
            'createCollectionAction' => new InputDefinition(array(
                new InputArgument('tasks', InputArgument::REQUIRED, 'Collection of task urls and test types')
            )),            
            'cancelAction' => new InputDefinition(array(
                new InputArgument('id', InputArgument::REQUIRED, 'ID of task to be cancelled')
            )),
            'cancelCollectionAction' => new InputDefinition(array(
                new InputArgument('ids', InputArgument::REQUIRED, 'IDs of tasks to be cancelled')
            ))  
            
        ));
        
        $this->setRequestTypes(array(
            'createAction' => 'POST',
            'createCollectionAction' => 'POST',
            'cancelAction' => 'POST',
            'cancelCollectionAction' => 'POST'
        ));
    }    
    
    public function createAction()
    {     
        if ($this->isInMaintenanceReadOnlyMode()) {
            return $this->sendServiceUnavailableResponse();
        }        
        
        if (!$this->getTaskTypeService()->has($this->getArguments('createAction')->get('type'))) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(400);
        } 
        
        $this->get('logger')->info('TaskController::createAction: request parameters: ['.$this->get('request')->request->get('parameters').']');
        
        $taskType = $this->getTaskTypeService()->fetch($this->getArguments('createAction')->get('type'));
        $parameters = (is_null($this->get('request')->request->get('parameters'))) ? '' : $this->get('request')->request->get('parameters');
        
        $this->get('logger')->info('TaskController::createAction: parameters: ['.$parameters.']');
        
        $task = $this->getTaskService()->create(
            $this->getArguments('createAction')->get('url'),
            $taskType,
            $parameters
        );
        
        $this->getTaskService()->getEntityManager()->persist($task);
        $this->getTaskService()->getEntityManager()->flush();

        $this->get('simplytestable.services.resque.queueService')->enqueue(
            $this->get('simplytestable.services.resque.jobFactoryService')->create(
                'task-perform',
                ['id' => $task->getId()]
            )
        );
        
        return $this->sendResponse($task);
    }
    
    
    public function createCollectionAction() {        
        if ($this->isInMaintenanceReadOnlyMode()) {
            return $this->sendServiceUnavailableResponse();
        }         
        
        $rawRequestTasks = $this->getArguments('createCollectionAction')->get('tasks');
        $tasks = array();
        
        foreach ($rawRequestTasks as $taskDetails) {
            if ($this->getTaskTypeService()->has($taskDetails['type'])) {
                $parameters = (!isset($taskDetails['parameters'])) ? '' : $taskDetails['parameters'];
                
                $task = $this->getTaskService()->create(
                    $taskDetails['url'],
                    $this->getTaskTypeService()->fetch($taskDetails['type']),
                    $parameters
                ); 
                
                $tasks[] = $task;                
                
                $this->getTaskService()->getEntityManager()->persist($task);               
            }              
        }
        
        $this->getTaskService()->getEntityManager()->flush();
        
        foreach ($tasks as $task) {
            $this->get('simplytestable.services.resque.queueService')->enqueue(
                $this->get('simplytestable.services.resque.jobFactoryService')->create(
                    'task-perform',
                    ['id' => $task->getId()]
                )
            );
        }

        return $this->sendResponse($tasks); 
    }
    
    
    public function cancelAction()
    {          
        if ($this->isInMaintenanceReadOnlyMode()) {
            return $this->sendServiceUnavailableResponse();
        }           
        
        $task = $this->getTaskService()->getById($this->getArguments('cancelAction')->get('id'));
        if (is_null($task)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(400);
        }

        $this->getTaskService()->cancel($task);        
        
        $this->getTaskService()->getEntityManager()->remove($task);
        $this->getTaskService()->getEntityManager()->flush();        
        
        return $this->sendSuccessResponse();
    }
    
    public function cancelCollectionAction()
    {
        if ($this->isInMaintenanceReadOnlyMode()) {
            return $this->sendServiceUnavailableResponse();
        }           
        
        $taskIds = explode(',', $this->getArguments('cancelCollectionAction')->get('ids'));
        
        $cancelledTaskCount = 0;
        foreach ($taskIds as $taskId) {
            $task = $this->getTaskService()->getById($taskId);
            if (!is_null($task)) {
                $this->getTaskService()->cancel($task);
                $this->getTaskService()->getEntityManager()->remove($task);
                $cancelledTaskCount++;
            }                          
        }
        
        if ($cancelledTaskCount > 0) {
            $this->getTaskService()->getEntityManager()->flush(); 
        }
        
        return $this->sendSuccessResponse();
    }
    
    
    
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\TaskService
     */
    private function getTaskService() {
        return $this->container->get('simplytestable.services.taskservice');
    }
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Services\TaskTypeService
     */
    private function getTaskTypeService() {
        return $this->container->get('simplytestable.services.tasktypeservice');
    }    
}
