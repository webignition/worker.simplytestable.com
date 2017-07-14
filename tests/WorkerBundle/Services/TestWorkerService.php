<?php
namespace Tests\WorkerBundle\Services;

use SimplyTestable\WorkerBundle\Entity\ThisWorker;
use SimplyTestable\WorkerBundle\Services\WorkerService;

class TestWorkerService extends WorkerService
{
    /**
     * @var int
     */
    private $activateResult;

    /**
     * @var ThisWorker
     */
    private $getResult;

    /**
     * @param int $activateResult
     */
    public function setActivateResult($activateResult)
    {
        $this->activateResult = $activateResult;
    }

    /**
     * @param ThisWorker $worker
     */
    public function setGetResult(ThisWorker $worker)
    {
        $this->getResult = $worker;
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

    /**
     * {@inheritdoc}
     */
    public function get()
    {
        if (!empty($this->getResult)) {
            return $this->getResult;
        }

        return parent::get();
    }
}
