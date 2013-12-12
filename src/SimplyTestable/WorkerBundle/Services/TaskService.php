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
use webignition\Http\Client\CurlException;

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
     * @var \SimplyTestable\WorkerBundle\Services\HttpClientService
     */
    private $httpClientService; 
    
    
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
     * @param \SimplyTestable\WorkerBundle\Services\HttpClientService $httpClientService
     */
    public function __construct(
            EntityManager $entityManager,
            Logger $logger,
            \SimplyTestable\WorkerBundle\Services\StateService $stateService,
            \SimplyTestable\WorkerBundle\Services\TaskDriver\FactoryService $taskDriverFactoryService,
            \SimplyTestable\WorkerBundle\Services\UrlService $urlService,
            \SimplyTestable\WorkerBundle\Services\CoreApplicationService$coreApplicationService,
            \SimplyTestable\WorkerBundle\Services\WorkerService $workerService,
            \SimplyTestable\WorkerBundle\Services\HttpClientService $httpClientService)
    {    
        parent::__construct($entityManager);
        
        $this->logger = $logger;
        $this->stateService = $stateService;
        $this->taskDriverFactoryService = $taskDriverFactoryService;
        $this->urlService = $urlService;
        $this->coreApplicationService = $coreApplicationService;
        $this->workerService = $workerService;
        $this->httpClientService = $httpClientService;
    }     
  

    /**
     *
     * @param string $url
     * @param TaskType $type
     * @param string $parameters
     * @return \SimplyTestable\WorkerBundle\Entity\Task\Task
     */
    public function create($url, TaskType $type, $parameters) {        
        $encoder = new \webignition\Url\Encoder();
        
        $task = new Task();
        $task->setState($this->getStartingState());
        $task->setType($type);
        $task->setUrl((string)$encoder->encode(new \webignition\NormalisedUrl\NormalisedUrl(($url))));
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
    public function getQueuedState() {
        return $this->getStartingState();
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
     * @return int 
     */
    public function perform(Task $task) {        
        $this->logger->info("TaskService::perform: [".$task->getId()."] [".$task->getState()->getName()."] Initialising");
        
        if (!$this->isQueued($task)) {
            $this->logger->info("TaskService::perform: [".$task->getId()."] Task state is [".$task->getState()->getName()."] and cannot be performed");
            return 1;
        }           
        
        /*  @var $taskDriver TaskDriver */
        $taskDriver = $this->taskDriverFactoryService->getTaskDriver($task);

        if ($taskDriver === false) {
            $this->logger->info("TaskService::perform: [".$task->getId()."] No driver found for task type \"".$task->getType()->getName()."\"");
            return 2;
        }
        
        $this->start($task);
       
        /* @var $output \SimplyTestable\WorkerBundle\Entity\Task\Output */
        $taskDriverResponse = $taskDriver->perform($task);
        
        $this->complete($task, $taskDriverResponse);
        return 0;
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
    public function complete(Task $task, TaskDriverResponse $taskDriverResponse) {
        if (!$task->getTimePeriod()->hasEndDateTime()) {
            $task->getTimePeriod()->setEndDateTime(new \DateTime());
        }        
        
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
        
        $this->removeTemporaryParameters($task); 
        
        $requestUrl = $this->urlService->prepare($this->coreApplicationService->get()->getUrl() . '/task/' . urlencode($task->getUrl()) . '/' . urlencode($task->getType()->getName()) . '/' . $task->getParametersHash() . '/complete/');       
        //$requestUrl = $this->urlService->prepare($this->coreApplicationService->get()->getUrl() . '/task/'.$this->workerService->get()->getHostname().'/'.$task->getId().'/complete/');       
        $httpRequest = $this->httpClientService->postRequest($requestUrl, null, array(
            'end_date_time' => $task->getTimePeriod()->getEndDateTime()->format('c'),
            'output' => $task->getOutput()->getOutput(),
            'contentType' => (string)$task->getOutput()->getContentType(),
            'state' => $task->getState()->getName(),
            'errorCount' => $task->getOutput()->getErrorCount(),
            'warningCount' => $task->getOutput()->getWarningCount()
        ));
        
        $this->logger->info("TaskService::reportCompletion: Reporting completion state to " . $requestUrl);
        
        try {
            $response = $this->httpClientService->get()->send($httpRequest);            
        } catch (\Guzzle\Http\Exception\CurlException $curlException) {
            $this->logger->err("TaskService::reportCompletion: " . $requestUrl . ": " . $curlException->getErrorNo());            
            return $curlException->getErrorNo();
        } catch (\Guzzle\Http\Exception\ClientErrorResponseException $clientErrorResponseException) {
            /* @var $response \Guzzle\Http\Message\Response */
            $response = $clientErrorResponseException->getResponse();
        } catch (\Guzzle\Http\Exception\ServerErrorResponseException $serverErrorResponseException) {            
            $response = $serverErrorResponseException->getResponse();
        }
        
        $this->logger->info("TaskService::reportCompletion: " . $requestUrl . ": " . $response->getStatusCode()." ".$response->getReasonPhrase());
        
        if (!$response->isSuccessful()) {
            $this->logger->err("TaskService::reportCompletion: Completion reporting failed for [".$task->getId()."] [".$task->getUrl()."]");
            $this->logger->err("TaskService::reportCompletion: [".$task->getId()."] " . $requestUrl . ": " . $response->getStatusCode()." ".$response->getReasonPhrase());
            
            if ($response->getStatusCode() !== 410) {
                return $response->getStatusCode();            
            }
        }
         
        $task->setNextState();        
        $this->persistAndFlush($task);

        return true;
    }
    
    /**
     *
     * @return \SimplyTestable\WorkerBundle\Repository\TaskRepository
     */
    public function getEntityRepository() {
        return parent::getEntityRepository();
    } 
    
    
    /**
     * 
     * @param \SimplyTestable\WorkerBundle\Entity\Task\Task $task
     */
    private function removeTemporaryParameters(Task $task) {        
        if (!$task->hasParameters()) {
            return;
        }
        
        $hasRemovedTemporaryParameters = false;
        $decodedParameters = json_decode($task->getParameters());
        
        foreach ($decodedParameters as $key => $value) {
            if (substr($key, 0, strlen('x-')) == 'x-') {
                unset($decodedParameters->$key);                
                $hasRemovedTemporaryParameters = true;
            }
        }
        
        if ($hasRemovedTemporaryParameters) {
            $task->setParameters(json_encode($decodedParameters));
        }       
    }
  
}