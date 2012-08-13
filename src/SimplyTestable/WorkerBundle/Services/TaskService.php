<?php
namespace SimplyTestable\WorkerBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Entity\Task\Output as TaskOutput;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;
use Symfony\Component\HttpKernel\Log\LoggerInterface as Logger;
use SimplyTestable\WorkerBundle\Entity\TimePeriod;

class TaskService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\WorkerBundle\Entity\Task\Task';
    const TASK_STARTING_STATE = 'task-queued';
    const TASK_IN_PROGRESS_STATE = 'task-in-progress';
    const TASK_COMPLETED_STATE = 'task-completed';
    
    /**
     *
     * @var Logger
     */
    private $logger;
        
    
    /**
     *
     * @var \SimplyTestable\WorkerBundle\Services\StateService 
     */
    private $stateService;   
    
    
    /**
     *
     * @var \SimplyTestable\WorkerBundle\Services\TaskDriver\FactoryService
     */
    private $taskDriverFactoryService;
    
    
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
     * @param Logger $logger
     * @param \SimplyTestable\WorkerBundle\Services\StateService $stateService
     * @param \SimplyTestable\WorkerBundle\Services\TaskDriver\FactoryService $taskDriverFactoryService
     */
    public function __construct(
            EntityManager $entityManager,
            Logger $logger,
            \SimplyTestable\WorkerBundle\Services\StateService $stateService,
            \SimplyTestable\WorkerBundle\Services\TaskDriver\FactoryService $taskDriverFactoryService)
    {    
        parent::__construct($entityManager);
        
        $this->logger = $logger;
        $this->stateService = $stateService;
        $this->taskDriverFactoryService = $taskDriverFactoryService;
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
        
        if ($this->has($task)) {
            return $this->fetch($task);
        }
        
        return $this->persistAndFlush($task);
    }
    
    
    /**
     *
     * @param Task $task
     * @return Task
     */
    private function fetch(Task $task) {
        return $this->getEntityRepository()->findOneBy(array(
            'state' => $task->getState(),
            'type' => $task->getType(),
            'url' => $task->getUrl()
        ));        
    }
    
    
    /**
     *
     * @param int $id
     * @return \SimplyTestable\WorkerBundle\Entity\Task\Task
     */
    public function getById($id) {
        return $this->getEntityRepository()->find($id);
    }    
    
    
    /**
     *
     * @param Task $task
     * @return boolean
     */
    private function has(Task $task) {        
        return !is_null($this->fetch($task));
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
    
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Entity\State 
     */
    public function getInProgressState() {
        return $this->stateService->fetch(self::TASK_IN_PROGRESS_STATE);
    }
    
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Entity\State 
     */
    public function getCompletedState() {
        return $this->stateService->fetch(self::TASK_COMPLETED_STATE);
    }    
    
    
    /**
     *
     * @param Task $task
     * @return boolean 
     */
    public function perform(Task $task) {        
        $this->logger->info("TaskService::perform: Initialising");        
        
        if (!$task->getState()->equals($this->getStartingState())) {            
            $this->logger->info("TaskService::perform: Task is not queued and cannot be performed");
            return true;
        }           
        
        $taskDriver = $this->taskDriverFactoryService->getTaskDriver($task);

        if ($taskDriver === false) {
            $this->logger->info("TaskService::perform: No driver found for task type \"".$task->getType()->getName()."\"");
            return false;
        }
        
        $this->start($task);
       
        /* @var $output \SimplyTestable\WorkerBundle\Entity\Task\Output */
        $output = $taskDriver->perform($task);       
        
        $this->complete($task, $output);
        return true;
    }
    
    
    /**
     *
     * @param Task $task
     * @return Task 
     */
    private function start(Task $task) {
        $timePeriod = new TimePeriod();
        $timePeriod->setStartDateTime(new \DateTime());        
        $task->setTimePeriod($timePeriod);        
        $task->setState($this->getInProgressState());        
        
        return $this->persistAndFlush($task);
    }
    
    
    /**
     *
     * @param Task $task
     * @param TaskOutput $output
     * @return Task 
     */
    private function complete(Task $task, TaskOutput $output) {
        $task->getTimePeriod()->setEndDateTime(new \DateTime());
        $task->setOutput($output);
        $task->setState($this->getCompletedState());
        
        return $this->persistAndFlush($task);        
    }
  
}