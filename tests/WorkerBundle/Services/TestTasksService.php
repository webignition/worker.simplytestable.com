<?php
namespace Tests\WorkerBundle\Services;

use SimplyTestable\WorkerBundle\Services\TasksService;

class TestTasksService extends TasksService
{
    /**
     * @var int
     */
    private $requestResult;

    /**
     * @param int $requestResult
     */
    public function setRequestResult($requestResult)
    {
        $this->requestResult = $requestResult;
    }

    /**
     * {@inheritdoc}
     */
    public function request($requestedLimit = null)
    {
        if (!is_null($this->requestResult)) {
            $requestResult = $this->requestResult;
            $this->requestResult = null;

            return $requestResult;
        }

        return parent::request($requestedLimit);
    }
}
