<?php
namespace SimplyTestable\WorkerBundle\Services;

use Doctrine\ORM\EntityManager;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Entity\State;
use SimplyTestable\WorkerBundle\Entity\Task\Output as TaskOutput;
use SimplyTestable\WorkerBundle\Entity\Task\Type\Type as TaskType;
use Symfony\Component\HttpKernel\Log\LoggerInterface as Logger;
use SimplyTestable\WorkerBundle\Entity\TimePeriod;
use SimplyTestable\WorkerBundle\Model\TaskDriver\Response as TaskDriverResponse;
use SimplyTestable\WorkerBundle\Services\TaskDriver\TaskDriver;

class TaskService extends EntityService {
    
    const ENTITY_NAME = 'SimplyTestable\WorkerBundle\Entity\Task\Task';
    const TASK_STARTING_STATE = 'task-queued';
    const TASK_IN_PROGRESS_STATE = 'task-in-progress';
    const TASK_COMPLETED_STATE = 'task-completed';
    const TASK_CANCELLED_STATE = 'task-cancelled';
    const TASK_FAILED_NO_RETRY_AVAILABLE_STATE = 'task-failed-no-retry-available';
    const TASK_FAILED_RETRY_AVAILABLE_STATE = 'task-failed-retry-available';
    const TASK_FAILED_RETRY_LIMIT_REACHED_STATE = 'task-failed-retry-limit-reached';
    const TASK_SKIPPED_STATE = 'task-skipped';

    
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
     * @var \SimplyTestable\WorkerBundle\Service\UrlService $urlService
     */
    private $urlService;
    
    
    /**
     *
     * @var \SimplyTestable\WorkerBundle\Service\CoreApplicationService $coreApplicationService
     */
    private $coreApplicationService;   
    
    
    /**
     *
     * @var \SimplyTestable\WorkerBundle\Service\WorkerService $workerService
     */
    private $workerService;
    
    
    /**
     *
     * @var \webignition\Http\Client\Client
     */
    private $httpClient; 
    
    
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
     * @param \SimplyTestable\WorkerBundle\Services\UrlService $urlService
     * @param \SimplyTestable\WorkerBundle\Services\CoreApplicationService $coreApplicationService
     * @param \SimplyTestable\WorkerBundle\Services\WorkerService $workerService
     * @param \webignition\Http\Client\Client $httpClient
     */
    public function __construct(
            EntityManager $entityManager,
            Logger $logger,
            \SimplyTestable\WorkerBundle\Services\StateService $stateService,
            \SimplyTestable\WorkerBundle\Services\TaskDriver\FactoryService $taskDriverFactoryService,
            \SimplyTestable\WorkerBundle\Services\UrlService $urlService,
            \SimplyTestable\WorkerBundle\Services\CoreApplicationService$coreApplicationService,
            \SimplyTestable\WorkerBundle\Services\WorkerService $workerService,
            \webignition\Http\Client\Client $httpClient)
    {    
        parent::__construct($entityManager);
        
        $this->logger = $logger;
        $this->stateService = $stateService;
        $this->taskDriverFactoryService = $taskDriverFactoryService;
        $this->urlService = $urlService;
        $this->coreApplicationService = $coreApplicationService;
        $this->workerService = $workerService;
        $this->httpClient = $httpClient;
    }     
  

    /**
     *
     * @param string $url
     * @param TaskType $type
     * @param string $parameters
     * @return \SimplyTestable\WorkerBundle\Entity\Task\Task
     */
    public function create($url, TaskType $type, $parameters) {        
        $task = new Task();
        $task->setState($this->getStartingState());
        $task->setType($type);
        $task->setUrl($url);
        $task->setParameters($parameters);
        
        if ($this->has($task)) {
            return $this->fetch($task);
        }
        
        return $task;
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
     * @return \SimplyTestable\WorkerBundle\Entity\State 
     */
    public function getCancelledState() {
        return $this->stateService->fetch(self::TASK_CANCELLED_STATE);
    }  
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Entity\State 
     */
    public function getFailedNoRetryAvailableState() {
        return $this->stateService->fetch(self::TASK_FAILED_NO_RETRY_AVAILABLE_STATE);
    }      
    
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Entity\State 
     */
    public function getFailedRetryAvailableState() {
        return $this->stateService->fetch(self::TASK_FAILED_RETRY_AVAILABLE_STATE);
    }   
    
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Entity\State 
     */
    public function getFailedRetryLimitReachedState() {
        return $this->stateService->fetch(self::TASK_FAILED_RETRY_LIMIT_REACHED_STATE);
    }       
    
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Entity\State 
     */
    public function getSkippedState() {
        return $this->stateService->fetch(self::TASK_SKIPPED_STATE);
    }     

    
    /**
     *
     * @param Task $task
     * @return boolean 
     */
    public function perform(Task $task) {        
        $this->logger->info("TaskService::perform: [".$task->getId()."] [".$task->getState()->getName()."] Initialising");        
        
        if (!$this->isQueued($task)) {
            $this->logger->info("TaskService::perform: [".$task->getId()."] Task state is [".$task->getState()->getName()."] and cannot be performed");
            return true;
        }           
        
        /*  @var $taskDriver TaskDriver */
        $taskDriver = $this->taskDriverFactoryService->getTaskDriver($task);

        if ($taskDriver === false) {
            $this->logger->info("TaskService::perform: [".$task->getId()."] No driver found for task type \"".$task->getType()->getName()."\"");
            return false;
        }
        
        $this->start($task);
       
        /* @var $output \SimplyTestable\WorkerBundle\Entity\Task\Output */
        $taskDriverResponse = $taskDriver->perform($task);
        
        $this->complete($task, $taskDriverResponse);
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
     * @param TaskDriverResponse $taskDriverResponse
     * @return Task 
     */
    private function complete(Task $task, TaskDriverResponse $taskDriverResponse) {        
        $task->getTimePeriod()->setEndDateTime(new \DateTime());
        $task->setOutput($taskDriverResponse->getTaskOutput());
        
        if ($taskDriverResponse->hasBeenSkipped()) {
            $completionState = $this->getSkippedState();
        } elseif ($taskDriverResponse->hasSucceeded()) {
            $completionState = $this->getCompletedState();
        } else {
            if ($taskDriverResponse->isRetryLimitReached()) {
                $completionState = $this->getFailedRetryLimitReachedState();
            } elseif ($taskDriverResponse->isRetryable()) {
                $completionState = $this->getFailedRetryAvailableState();
            } else {
                $completionState = $this->getFailedNoRetryAvailableState();
            }
        }
        
        return $this->finish($task, $completionState);
    }
    
    
    /**
     *
     * @param Task $task
     * @return Task 
     */
    public function cancel(Task $task) {
        if ($this->isCancelled($task)) {
            return $task;
        }
        
        if  ($this->isCompleted($task)) {
            return $task;
        }
        
        return $this->finish($task, $this->getCancelledState());
    }
    
    
    /**
     *
     * @param Task $task
     * @return boolean 
     */
    private function isCancelled(Task $task) {
        return $task->getState()->equals($this->getCancelledState());
    }
    
    
    /**
     *
     * @param Task $task
     * @return boolean 
     */
    private function isCompleted(Task $task) {
        return $task->getState()->equals($this->getCompletedState());
    }        

    /**
     *
     * @param Task $task
     * @return boolean 
     */
    private function isQueued(Task $task) {
        return $task->getState()->equals($this->getStartingState());
    }
    
    
    /**
     *
     * @param Task $task
     * @param State $state
     * @return Task 
     */
    private function finish(Task $task, State $state) {
        $task->setState($state);
        return $this->persistAndFlush($task);
    }
    
    
    /**
     *
     * @param Task $task
     * @return boolean 
     */
    public function reportCompletion(Task $task) {        
        $this->logger->info("TaskService::reportCompletion: Initialising [".$task->getId()."]");        
        
        if (!$task->hasOutput()) {
            $this->logger->info("TaskService::reportCompletion: Task state is [".$task->getState()->getName()."], we can't report back just yet");
            return true;
        }
        
        $requestUrl = $this->urlService->prepare($this->coreApplicationService->get()->getUrl() . '/task/'.$this->workerService->get()->getHostname().'/'.$task->getId().'/complete/');
        
        $httpRequest = new \HttpRequest($requestUrl, HTTP_METH_POST);
        $httpRequest->setPostFields(array(
            'end_date_time' => $task->getTimePeriod()->getEndDateTime()->format('c'),
            'output' => $task->getOutput()->getOutput(),
            'contentType' => (string)$task->getOutput()->getContentType(),
            'state' => $task->getState()->getName(),
            'errorCount' => $task->getOutput()->getErrorCount(),
            'warningCount' => $task->getOutput()->getWarningCount()
        ));
        
        $this->logger->info("TaskService::reportCompletion: Reporting completion state to " . $requestUrl);
        
        try {
            $response = $this->httpClient->getResponse($httpRequest);
            
            if ($this->httpClient instanceof \webignition\Http\Mock\Client\Client) {
                $this->logger->info("TaskService::reportCompletion: response fixture path: " . $this->httpClient->getStoredResponseList()->getRequestFixturePath($httpRequest));
            }            
            
            $this->logger->info("TaskService::reportCompletion: " . $requestUrl . ": " . $response->getResponseCode()." ".$response->getResponseStatus());
            
            if ($response->getResponseCode() !== 200) {
                $this->logger->err("TaskService::reportCompletion: Completion reporting failed for [".$task->getId()."] [".$task->getUrl()."]");
                $this->logger->err("TaskService::reportCompletion: [".$task->getId()."] " . $requestUrl . ": " . $response->getResponseCode()." ".$response->getResponseStatus());
                return false;
            }
            
            $task->setNextState();        
            $this->persistAndFlush($task);

            return true;            
            
        } catch (CurlException $curlException) {
            $this->logger->info("TaskService::reportCompletion: " . $requestUrl . ": " . $curlException->getMessage());            
            return false;
        }
    }    
  
}