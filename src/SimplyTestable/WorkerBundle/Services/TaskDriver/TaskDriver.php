<?php

namespace SimplyTestable\WorkerBundle\Services\TaskDriver;

use SimplyTestable\WorkerBundle\Entity\Task\Output as TaskOutput;
use SimplyTestable\WorkerBundle\Entity\Task\Task;
use SimplyTestable\WorkerBundle\Services\StateService;
use SimplyTestable\WorkerBundle\Services\HttpClientService;
use SimplyTestable\WorkerBundle\Model\TaskDriver\Response as TaskDriverResponse;
use webignition\InternetMediaType\InternetMediaType;

abstract class TaskDriver
{
    const OUTPUT_STARTING_STATE = 'taskoutput-queued';

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var HttpClientService
     */
    private $httpClientService;

    /**
     *
     * @var TaskDriverResponse
     */
    protected $response = null;

    /**
     * @param StateService $stateService
     */
    protected function setStateService(StateService $stateService)
    {
        $this->stateService = $stateService;
    }

    /**
     * @param Task $task
     *
     * @return TaskDriverResponse
     */
    public function perform(Task $task)
    {
        $this->response = new TaskDriverResponse();

        $rawOutput = $this->execute($task);

        $output = new TaskOutput();
        $output->setOutput($rawOutput);
        $output->setContentType($this->getOutputContentType());
        $output->setState($this->stateService->fetch(self::OUTPUT_STARTING_STATE));
        $output->setErrorCount($this->response->getErrorCount());
        $output->setWarningCount($this->response->getWarningCount());

        $this->response->setTaskOutput($output);

        return $this->response;
    }

    /**
     * @param Task $task
     *
     * @return string
     */
    abstract protected function execute(Task $task);

    /**
     * @return InternetMediaType
     */
    abstract protected function getOutputContentType();

    /**
     * @param HttpClientService $httpClientService
     */
    protected function setHttpClientService(HttpClientService $httpClientService)
    {
        $this->httpClientService = $httpClientService;
    }

    /**
     *
     * @return HttpClientService
     */
    protected function getHttpClientService()
    {
        return $this->httpClientService;
    }
}
