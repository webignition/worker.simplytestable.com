<?php
namespace Tests\WorkerBundle\Services;

use SimplyTestable\WorkerBundle\Services\WorkerService;

class TestWorkerService extends WorkerService
{
    /**
     * @var int
     */
    private $activateResult;

    /**
     * @param int $activateResult
     */
    public function setActivateResult($activateResult)
    {
        $this->activateResult = $activateResult;
    }

    /**
     * {@inheritdoc}
     */
    public function activate()
    {
        if (!is_null($this->activateResult)) {
            $activateResult = $this->activateResult;
            $this->activateResult = null;

            return $activateResult;
        }
        return parent::activate();
    }
}
