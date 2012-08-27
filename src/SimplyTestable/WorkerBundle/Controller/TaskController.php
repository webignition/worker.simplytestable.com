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
            'cancelAction' => new InputDefinition(array(
                new InputArgument('id', InputArgument::REQUIRED, 'ID of task to be cancelled')
            ))            
        ));
        
        $this->setRequestTypes(array(
            'createAction' => HTTP_METH_POST,
            'cancelAction' => HTTP_METH_POST            
        ));
    }    
    
    public function createAction()
    {        
        if (!$this->getTaskTypeService()->has($this->getArguments('createAction')->get('type'))) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(400);
        }  
        
        $taskType = $this->getTaskTypeService()->fetch($this->getArguments('createAction')->get('type'));
        
        $task = $this->getTaskService()->create(
            $this->getArguments('activateAction')->get('url'),
            $taskType
        );
        
        $this->container->get('simplytestable.services.resqueQueueService')->add(
            'SimplyTestable\WorkerBundle\Resque\Job\TaskPerformJob',
            'task-perform',
            array(
                'id' => $task->getId()
            )                
        );
        
        return $this->sendResponse($task);
    }
    
    
    public function cancelAction()
    {
        if (!$this->getTaskTypeService()->has($this->getArguments('cancelAction')->get('id'))) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(400);
        }
        
        $task = $this->getTaskService()->getById($this->getArguments('cancelAction')->get('id'));
        $this->getTaskService()->cancel($task);
        
        return $this->sendResponse($task);
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
