<?php
namespace SimplyTestable\WorkerBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;


class TaskService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\WorkerBundle\Entity\Task\Task';
    const TASK_STARTING_STATE = 'task-queued';
    
    /**
     *
     * @var \SimplyTestable\WorkerBundle\Services\StateService 
     */
    private $stateService;    
    
    /**
     *
     * @return string
     */
    protected function getEntityName() {
        return self::ENTITY_NAME;
    }
    
    
    /**
     *
     * @param \Doctrine\ORM\EntityManager $entityManager
     * @param \SimplyTestable\WorkerBundle\Services\StateService $stateService
     */
    public function __construct(
            EntityManager $entityManager,
            \SimplyTestable\WorkerBundle\Services\StateService $stateService)
    {    
        parent::__construct($entityManager);
        
        $this->stateService = $stateService;
    }     
  

    /**
     *
     * @param string $url
     * @param TaskType $type
     * @return \SimplyTestable\WorkerBundle\Entity\Task\Task
     */
    public function create($url, TaskType $type) {
        $task = new Task();
        $task->setState($this->getStartingState());
        $task->setType($type);
        $task->setUrl($url);
        
        return $this->persistAndFlush($task);
    }
    
    /**
     *
     * @param Task $task
     * @return \SimplyTestable\WorkerBundle\Entity\Task\Task
     */
    public function persistAndFlush(Task $task) {
        $this->getEntityManager()->persist($task);
        $this->getEntityManager()->flush();
        return $task;
    }   
    
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Entity\State 
     */
    public function getStartingState() {
        return $this->stateService->fetch(self::TASK_STARTING_STATE);
    }
}