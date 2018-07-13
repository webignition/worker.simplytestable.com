<?php

namespace SimplyTestable\AppBundle\Request\Task;

class CancelRequestCollection
{
    /**
     * @var CancelRequest[]
     */
    private $cancelRequests;

    /**
     * @param CancelRequest[]
     */
    public function __construct($createRequests)
    {
        $this->cancelRequests = $createRequests;
    }

    /**
     * @return CancelRequest[]
     */
    public function getCancelRequests()
    {
        return $this->cancelRequests;
    }
}
