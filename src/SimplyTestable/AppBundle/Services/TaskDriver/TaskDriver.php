<?php

namespace SimplyTestable\AppBundle\Services\TaskDriver;

use SimplyTestable\AppBundle\Entity\Task\Output as TaskOutput;
use SimplyTestable\AppBundle\Entity\Task\Task;
use SimplyTestable\AppBundle\Services\StateService;
use SimplyTestable\AppBundle\Model\TaskDriver\Response as TaskDriverResponse;
use webignition\InternetMediaType\InternetMediaType;

abstract class TaskDriver
{
    const OUTPUT_STARTING_STATE = 'taskoutput-queued';

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var TaskDriverResponse
     */
    protected $response = null;

    /**
     * @param StateService $stateService
     */
    public function __construct(StateService $stateService)
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
}
