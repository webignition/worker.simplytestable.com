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
            'createAction' => HTTP_METH_POST,
            'createCollectionAction' => HTTP_METH_POST,
            'cancelAction' => HTTP_METH_POST,
            'cancelCollectionAction' => HTTP_METH_POST   
        ));
    }    
    
    public function createAction()
    {        
        if (!$this->getTaskTypeService()->has($this->getArguments('createAction')->get('type'))) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(400);
        } 
        
        $this->get('logger')->info('TaskController::createAction: request parameters: ['.$this->get('request')->request->get('parameters').']');
        
        $taskType = $this->getTaskTypeService()->fetch($this->getArguments('createAction')->get('type'));
        $parameters = (is_null($this->get('request')->request->get('parameters'))) ? '' : $this->get('request')->request->get('parameters');
        
        $this->get('logger')->info('TaskController::createAction: parameters: ['.$parameters.']');
        
        $task = $this->getTaskService()->create(
            $this->getArguments('activateAction')->get('url'),
            $taskType,
            $parameters
        );
        
        $this->getTaskService()->getEntityManager()->persist($task);
        $this->getTaskService()->getEntityManager()->flush();
        
        $this->container->get('simplytestable.services.resqueQueueService')->add(
            'task-perform',
            array(
                'id' => $task->getId()
            )                
        );
        
        return $this->sendResponse($task);
    }
    
    
    public function createCollectionAction() {        
        $rawRequestTasks = $this->getArguments('createCollectionAction')->get('tasks');
        $tasks = array();
        
        foreach ($rawRequestTasks as $taskDetails) {
            if ($this->getTaskTypeService()->has($taskDetails['type'])) {
                $parameters = (isset($taskDetails['parameters'])) ? '' : $taskDetails['parameters'];
                
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
            $this->container->get('simplytestable.services.resqueQueueService')->add(
                'task-perform',
                array(
                    'id' => $task->getId()
                )                
            );            
        }

        return $this->sendResponse($tasks); 
    }
    
    
    public function cancelAction()
    {        
        $task = $this->getTaskService()->getById($this->getArguments('cancelAction')->get('id'));
        if (is_null($task)) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(400);
        }

        $this->getTaskService()->cancel($task);        
        return $this->sendResponse($task);
    }
    
    public function cancelCollectionAction()
    {
        $taskIds = explode(',', $this->getArguments('cancelCollectionAction')->get('ids'));
        foreach ($taskIds as $taskId) {
            $task = $this->getTaskService()->getById($taskId);
            if (!is_null($task)) {
                $this->getTaskService()->cancel($task);
            }                          
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
